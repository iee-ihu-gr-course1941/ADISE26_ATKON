$(async function () {
    const user = await authGuard();
    if (!user) return;

    $('body').show();
    $('#welcome-user').text(`Welcome, ${user.username}`);
    $('#user-stats').text(`Wins: ${user.wins} / Losses: ${user.losses}`);

    async function searchLobbies() {
        const $refreshButton = $('#refresh-lobbies-btn').prop('disabled', true);
        $('#lobbies-error').text('');
        $('#current-lobby-error').text('');

        try {
            const response = await fetch('../api/lobby/search.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Failed to load lobbies.');
            }

            renderUserLobby(data.user_lobby);
            const $list = $('#lobbies-list').empty();

            if (!data.lobbies.length) {
                $list.append('<p class="empty-lobbies">No public lobbies available.</p>');
                return;
            }

            data.lobbies.forEach(function (lobby) {
                const $card = $('<article>', { class: 'lobby-card' });
                const $info = $('<div>').append(
                    $('<strong>').text(lobby.host_username),
                    $('<span>').text(
                        `Room ${lobby.room_code} • ${lobby.player_count}/${lobby.max_players} players • ` +
                        (lobby.status === 'active' ? 'In progress' : 'Waiting')
                    )
                );
                const $joinButton = $('<button>', {
                    type: 'button',
                    class: 'join-lobby-btn',
                    'data-lobby-id': lobby.id
                })
                    .text(
                        lobby.status === 'active'
                            ? 'In Progress'
                            : (data.user_lobby && Number(data.user_lobby.id) === Number(lobby.id) ? 'Your Lobby' : 'Join')
                    )
                    .prop('disabled', Boolean(data.user_lobby) || lobby.status === 'active');

                $card.append($info, $joinButton);
                $list.append($card);
            });
        } catch (err) {
            console.error('Lobby search failed:', err);
            $('#lobbies-error').text(err.message || 'Failed to load lobbies.');
        } finally {
            $refreshButton.prop('disabled', false);
        }
    }

    function renderUserLobby(lobby) {
        const $info = $('#current-lobby-info').empty();

        if (!lobby) {
            $info.hide();
            $('#create-lobby-form').show();
            $('#join-by-code-form').show();
            return;
        }

        $('#create-lobby-form').hide();
        $('#join-by-code-form').hide();
        const isHost = Number(lobby.host_user_id) === Number(user.id);
        const $members = $('<ul>', { class: 'lobby-members' });
        lobby.players.forEach(function (player) {
            const pawnClass = `pawn-swatch-${player.pawn.toLowerCase()}`;
            $members.append(
                $('<li>').append(
                    $('<span>', {
                        class: `pawn-swatch ${pawnClass}`,
                        'aria-hidden': 'true'
                    }),
                    $('<span>').text(`${player.username} — ${player.colour}`)
                )
            );
        });

        $info.append(
            $('<p>').append(
                $('<strong>').text(lobby.room_code)
            ),
            $('<p>').text(isHost ? 'You are the host' : `Host: ${lobby.host_username}`),
            $('<p>').text(`${lobby.player_count}/${lobby.max_players} players`),
            $('<p>').text(`${lobby.available_slots} open slot${lobby.available_slots === 1 ? '' : 's'}`),
            $('<p>').text(
                `${lobby.is_public ? 'Public' : 'Private'} • ` +
                (lobby.status === 'active' ? 'In progress' : 'Waiting')
            ),
            $('<h3>').text('Players'),
            $members
        );

        if (lobby.status === 'lobby' && isHost) {
            $info.append(
                $('<button>', {
                    type: 'button',
                    id: 'start-game-btn',
                    disabled: true
                }).text('Start Game')
            );
        }

        if (lobby.status === 'lobby') {
            $info.append(
                $('<button>', {
                    type: 'button',
                    id: 'leave-lobby-btn'
                }).text('Leave Lobby')
            );
        }

        $info.show();
    }

    $('#create-lobby-form').on('submit', async function (event) {
        event.preventDefault();

        const $button = $('#create-lobby-btn').prop('disabled', true);
        $('#current-lobby-error').text('');

        try {
            const response = await fetch('../api/lobby/create.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    max_players: Number($('#create-max-players').val()),
                    is_public: $('#create-is-public').is(':checked')
                })
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Failed to create lobby.');
            }

            await searchLobbies();
        } catch (err) {
            console.error('Lobby creation failed:', err);
            $('#current-lobby-error').text(err.message || 'Failed to create lobby.');
        } finally {
            $button.prop('disabled', false);
        }
    });

    async function joinLobby(details, $button, $error) {
        $button.prop('disabled', true);
        $error.text('');

        try {
            const response = await fetch('../api/lobby/join.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(details)
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Failed to join lobby.');
            }

            await searchLobbies();
        } catch (err) {
            console.error('Lobby join failed:', err);
            $error.text(err.message || 'Failed to join lobby.');
        } finally {
            $button.prop('disabled', false);
        }
    }

    $('#lobbies-list').on('click', '.join-lobby-btn', function () {
        joinLobby(
            { lobby_id: Number($(this).data('lobby-id')) },
            $(this),
            $('#lobbies-error')
        );
    });

    $('#join-by-code-form').on('submit', function (event) {
        event.preventDefault();
        joinLobby(
            { room_code: $('#join-room-code').val().trim().toUpperCase() },
            $('#join-by-code-btn'),
            $('#current-lobby-error')
        );
    });

    $('#current-lobby-info').on('click', '#leave-lobby-btn', async function () {
        const $button = $(this).prop('disabled', true);
        $('#current-lobby-error').text('');

        try {
            const response = await fetch('../api/lobby/leave.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Failed to leave lobby.');
            }

            await searchLobbies();
        } catch (err) {
            console.error('Lobby leave failed:', err);
            $('#current-lobby-error').text(err.message || 'Failed to leave lobby.');
            $button.prop('disabled', false);
        }
    });

    searchLobbies();
    $('#refresh-lobbies-btn').on('click', searchLobbies);

    $('#logout-btn').on('click', async function () {
        const $btn = $(this).prop('disabled', true);

        try {
            await fetch('../api/user/logout.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
        } catch (err) {
            console.error('Logout request failed:', err);
        } finally {
            window.location.replace('../index.html');
        }
    });
});