<script>
    import { onMount } from "svelte";
    import axios from "axios";
    import Login from "./Pages/Auth/Login.svelte";
    import Register from "./Pages/Auth/Register.svelte";
    import ChatLayout from "./Pages/ChatLayout.svelte";

    let currentPage = $state("login");
    let user = $state(null);
    let initializing = $state(true);

    onMount(async () => {
        const token = localStorage.getItem("auth_token");
        if (!token) {
            initializing = false;
            return;
        }

        try {
            const response = await axios.get("/user");
            if (response.data) {
                user = response.data;
                currentPage = "chat";
            }
        } catch (err) {
            localStorage.removeItem("auth_token");
        } finally {
            initializing = false;
        }
    });

    function navigate(page) {
        currentPage = page;
    }

    function handleLogin(userData) {
        user = userData;
        currentPage = "chat";
    }

    async function handleLogout() {
        try {
            await axios.post("/logout");
        } catch (err) {
            console.error("Logout failed:", err);
        } finally {
            localStorage.removeItem("auth_token");
            user = null;
            currentPage = "login";
        }
    }
</script>

<main class="antialiased text-gray-900 font-sans">
    {#if initializing}
        <div class="min-h-screen flex items-center justify-center bg-gray-100">
            <div
                class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-600"
            ></div>
        </div>
    {:else if currentPage === "login"}
        <Login
            onNavigateRegister={() => navigate("register")}
            onLoginSuccess={handleLogin}
        />
    {:else if currentPage === "register"}
        <Register
            onNavigateLogin={() => navigate("login")}
            onRegisterSuccess={handleLogin}
        />
    {:else if currentPage === "chat"}
        <ChatLayout {user} onLogout={handleLogout} />
    {/if}
</main>

<style>
    :global(body) {
        margin: 0;
        padding: 0;
        background-color: #f3f4f6;
    }
</style>
