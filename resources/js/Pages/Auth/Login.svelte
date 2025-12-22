<script>
    import axios from "axios";

    let { onNavigateRegister, onLoginSuccess } = $props();

    let nickname = $state("");
    let password = $state("");
    let remember = $state(false);
    let error = $state("");
    let loading = $state(false);

    async function handleSubmit(e) {
        e.preventDefault();
        loading = true;
        error = "";

        try {
            const response = await axios.post("/login", {
                nickname,
                password,
                remember,
            });

            if (response.data && response.data.user) {
                if (response.data.token) {
                    localStorage.setItem("auth_token", response.data.token);
                }
                onLoginSuccess(response.data.user);
            }
        } catch (err) {
            if (err.response?.data?.errors) {
                // Handle Laravel validation errors
                const validationErrors = err.response.data.errors;
                const firstKey = Object.keys(validationErrors)[0];
                error = validationErrors[firstKey][0];
            } else {
                error =
                    err.response?.data?.message ||
                    "Invalid credentials. Please try again.";
            }
        } finally {
            loading = false;
        }
    }
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-8">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-800">Welcome Back</h1>
                <p class="text-gray-500 mt-2">
                    Enter your details to access your account
                </p>
            </div>

            {#if error}
                <div
                    class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm"
                >
                    {error}
                </div>
            {/if}

            <form onsubmit={handleSubmit} class="space-y-6">
                <div>
                    <label
                        for="nickname"
                        class="block text-sm font-medium text-gray-700 mb-1"
                        >Nickname</label
                    >
                    <input
                        type="nick"
                        id="nickname"
                        bind:value={nickname}
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                    />
                </div>

                <div>
                    <label
                        for="password"
                        class="block text-sm font-medium text-gray-700 mb-1"
                        >Password</label
                    >
                    <input
                        type="password"
                        id="password"
                        bind:value={password}
                        required
                        placeholder="••••••••"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                    />
                </div>

                <button
                    type="submit"
                    disabled={loading}
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors shadow-lg shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {#if loading}
                        <span class="inline-block animate-spin mr-2">↻</span> Signing
                        in...
                    {:else}
                        Sign In
                    {/if}
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-gray-600">
                    <button
                        onclick={onNavigateRegister}
                        class="text-blue-600 font-semibold hover:underline"
                    >
                        Sign up
                    </button>
                </p>
            </div>
        </div>
    </div>
</div>
