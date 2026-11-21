$(async function () {
    const user = await authGuard();
    if (!user) return;

    $('body').show();
    $('#welcome-user').text(`Welcome, ${user.username} (W: ${user.wins} / L: ${user.losses})`);

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