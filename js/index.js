$(function () {
    // 1. Redirect immediately if the user already has an active session
    fetch('api/user/me.php', { method: 'GET', credentials: 'same-origin' })
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (data && data.success && data.user) {
                window.location.replace('html/lobbies.html');
            }
        })
        .catch(() => {});

    // 2. Toggle between Login and Register tabs
    $('.tab-btn').on('click', function () {
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');

        $('#auth-error').text('');
        $('.auth-form').hide();
        $($(this).data('target')).show();
    });

    // 3. Handle Login submission
    $('#login-form').on('submit', async function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]').prop('disabled', true);
        $('#auth-error').text('');

        const username = $('#login-username').val().trim();
        const password = $('#login-password').val();

        try {
            const response = await fetch('api/user/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                $('#auth-error').text(data.error || 'Login failed.');
                return;
            }

            window.location.replace('html/lobbies.html');
        } catch (err) {
            $('#auth-error').text('Network error. Please try again.');
        } finally {
            $btn.prop('disabled', false);
        }
    });

    // 4. Handle Register submission
    $('#register-form').on('submit', async function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]').prop('disabled', true);
        $('#auth-error').text('');

        const username = $('#register-username').val().trim();
        const password = $('#register-password').val();

        try {
            const response = await fetch('api/user/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                $('#auth-error').text(data.error || 'Registration failed.');
                return;
            }

            window.location.replace('html/lobbies.html');
        } catch (err) {
            $('#auth-error').text('Network error. Please try again.');
        } finally {
            $btn.prop('disabled', false);
        }
    });
});