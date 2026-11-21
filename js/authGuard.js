async function authGuard() {
    try {
        const response = await fetch('../api/user/me.php', {
                                                        method: 'GET',
                                                        credentials: "same-origin"
        });
        if (!response.ok) {
            window.location.replace('../index.html');
            return null;
        }

        const data = await response.json();
        if (!data.success || !data.user) {
            window.location.replace('../index.html');
            return null;
        }

        return data.user;
    }
    catch (error) {
        window.location.replace('../index.html');
        return null;
    }
}