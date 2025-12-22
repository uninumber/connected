<script>
    import axios from "axios";

    let { onNavigateLogin, onRegisterSuccess } = $props();

    let name = $state("");
    let nickname = $state("");
    let password = $state("");
    let password_confirmation = $state("");
    let error = $state("");
    let loading = $state(false);

    async function handleSubmit(e) {
        e.preventDefault();

        if (password !== password_confirmation) {
            error = "Passwords do not match.";
            return;
        }

        loading = true;
        error = "";

        try {
            const response = await axios.post("/register", {
                name,
                nickname,
                password,
                password_confirmation,
            });

            if (response.data && response.data.user) {
                if (response.data.token) {
                    localStorage.setItem("auth_token", response.data.token);
                }
                onRegisterSuccess(response.data.user);
            }
        } catch (err) {
            if (err.response?.data?.errors) {
                // Handle Laravel validation errors specifically
                const validationErrors = err.response.data.errors;
                const firstKey = Object.keys(validationErrors)[0];
                error = validationErrors[firstKey][0];
            } else {
                error =
                    err.response?.data?.message ||
                    "Registration failed. Please try again.";
            }
        } finally {
            loading = false;
        }
    }
</script>

<div
    class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-12"
>
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-8">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-800">Create Account</h1>
                <p class="text-gray-500 mt-2">Join our chat community today</p>
            </div>

            {#if error}
                <div
                    class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm"
                >
                    {error}
                </div>
            {/if}

            <form onsubmit={handleSubmit} class="space-y-5">
                <div>
                    <label
                        for="nickname"
                        class="block text-sm font-medium text-gray-700 mb-1"
                        >Nickname</label
                    >
                    <input
                        type="nickname"
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

                <div>
                    <label
                        for="password_confirmation"
                        class="block text-sm font-medium text-gray-700 mb-1"
                        >Confirm Password</label
                    >
                    <input
                        type="password"
                        id="password_confirmation"
                        bind:value={password_confirmation}
                        required
                        placeholder="••••••••"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                    />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        disabled={loading}
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors shadow-lg shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {#if loading}
                            <span class="inline-block animate-spin mr-2">↻</span
                            > Creating account...
                        {:else}
                            Sign Up
                        {/if}
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center">
                <p class="text-gray-600">
                    Already have an account?
                    <button
                        onclick={onNavigateLogin}
                        class="text-blue-600 font-semibold hover:underline"
                    >
                        Sign in
                    </button>
                </p>
            </div>
        </div>

        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <p
                class="text-[10px] text-gray-400 uppercase tracking-widest font-bold"
            >
                Privacy Guaranteed
            </p>
        </div>
    </div>
</div>
