$(async function () {
    const user = await authGuard();
    if (!user) return;
});