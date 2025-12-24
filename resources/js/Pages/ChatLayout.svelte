<script>
    import { onMount } from "svelte";
    import Chat from "../Components/Chat.svelte";
    import axios from "axios";

    let { user, onLogout } = $props();

    let activeChatId = $state(null);
    let searchQuery = $state("");
    let chats = $state([]);
    let searchResults = $state({ chats: [], users: [] });
    let isSearching = $state(false);

    onMount(async () => {
        await fetchChats();

        window.Echo.private(`user.${user.id}.chats`).listen(
            ".MessageSent",
            (e) => {
                const message = e.message;
                const chatIndex = chats.findIndex(
                    (chat) => chat.id === message.chat_id,
                );
                if (chatIndex !== -1) {
                    const updatedChat = {
                        ...chats[chatIndex],
                        last_message: `${message.user.nickname}: ${message.text}`,
                        time: "Just now",
                        unread:
                            message.chat_id !== activeChatId
                                ? (chats[chatIndex].unread || 0) + 1
                                : chats[chatIndex].unread || 0,
                    };

                    const newChats = [...chats];
                    newChats.splice(chatIndex, 1);
                    chats = [updatedChat, ...newChats];
                }
            },
        );
    });

    async function fetchChats() {
        try {
            const response = await axios.get(`/users/chats`);
            chats = response.data;
            if (chats.length > 0 && !activeChatId) {
                activeChatId = chats[0].id;
            }
        } catch (error) {
            console.error("Error fetching chats:", error);
        }
    }

    $effect(() => {
        const query = searchQuery.trim();
        if (query.length > 0) {
            const timer = setTimeout(async () => {
                isSearching = true;
                try {
                    const response = await axios.get(
                        `/search?q=${encodeURIComponent(query)}`,
                    );
                    searchResults = response.data;
                } catch (error) {
                    console.error("Search failed:", error);
                } finally {
                    isSearching = false;
                }
            }, 300);
            return () => clearTimeout(timer);
        } else {
            searchResults = { chats: [], users: [] };
            isSearching = false;
        }
    });

    let activeChat = $derived(chats.find((c) => c.id === activeChatId) || null);

    function selectChat(id) {
        activeChatId = id;
        searchQuery = "";
        activeChat = chats.find((c) => c.id === id);
        activeChat.unread = 0;
    }

    async function startChatWithUser(targetUser) {
        const existingChat = chats.find((c) =>
            c.users?.some((u) => u.id === targetUser.id),
        );

        if (existingChat) {
            selectChat(existingChat.id);
            return;
        }

        const response = await axios.post(`/users/chats`, {
            user_id: targetUser.id,
        });

        const newChat = response.data.chat;

        chats = [newChat, ...chats];

        activeChatId = newChat.id;

        searchQuery = "";
    }
</script>

<div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside
        class="w-80 bg-white border-r border-gray-200 flex flex-col shrink-0 shadow-lg z-10"
    >
        <!-- Sidebar Header -->
        <div class="p-4 border-b border-gray-100 bg-white">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold shadow-md"
                    >
                        {user?.nickname?.charAt(0).toUpperCase() || "U"}
                    </div>
                    <div>
                        <h2
                            class="text-sm font-bold text-gray-800 leading-tight"
                        >
                            {user?.nickname || "Guest"}
                        </h2>
                        <span
                            class="text-[10px] text-green-500 font-bold uppercase tracking-wider"
                            >Online</span
                        >
                    </div>
                </div>
                <button
                    onclick={onLogout}
                    class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors"
                    title="Logout"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                        />
                    </svg>
                </button>
            </div>

            <!-- Search Input -->
            <div class="relative">
                <input
                    type="text"
                    bind:value={searchQuery}
                    placeholder="Search chats or users..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none"
                />
                <div class="absolute left-3 top-2.5">
                    {#if isSearching}
                        <div
                            class="h-4 w-4 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"
                        ></div>
                    {:else}
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-blue-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    {/if}
                </div>
            </div>
        </div>

        <!-- Scrollable Area -->
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            {#if searchQuery.trim().length > 0}
                <!-- Search Results View -->
                <div class="p-2">
                    {#if searchResults.chats.length > 0}
                        <h3
                            class="px-3 py-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                        >
                            Your Chats
                        </h3>
                        {#each searchResults.chats as chat (chat.id)}
                            <button
                                onclick={() => selectChat(chat.id)}
                                class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 rounded-xl transition-colors text-left"
                            >
                                <div
                                    class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold"
                                >
                                    {chat.name?.charAt(0) || "C"}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4
                                        class="text-sm font-bold text-gray-800 truncate"
                                    >
                                        {chat.name}
                                    </h4>
                                    <p class="text-xs text-gray-500 truncate">
                                        {chat.last_message || "No messages"}
                                    </p>
                                </div>
                            </button>
                        {/each}
                    {/if}

                    {#if searchResults.users.length > 0}
                        <h3
                            class="px-3 py-2 mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                        >
                            Global Users
                        </h3>
                        {#each searchResults.users as item (item.id)}
                            <button
                                onclick={() => startChatWithUser(item)}
                                class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 rounded-xl transition-colors text-left"
                            >
                                <div
                                    class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold"
                                >
                                    {item.nickname.charAt(0).toUpperCase()}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4
                                        class="text-sm font-bold text-gray-800 truncate"
                                    >
                                        {item.nickname}
                                    </h4>
                                    <p
                                        class="text-[10px] text-blue-500 font-semibold uppercase"
                                    >
                                        Start conversation
                                    </p>
                                </div>
                            </button>
                        {/each}
                    {/if}

                    {#if !isSearching && searchResults.chats.length === 0 && searchResults.users.length === 0}
                        <div class="p-8 text-center text-gray-400 text-sm">
                            No results found for "{searchQuery}"
                        </div>
                    {/if}
                </div>
            {:else}
                <!-- Normal Chat List -->
                {#if chats.length === 0}
                    <div class="p-8 text-center text-gray-400 text-sm">
                        No conversations yet.<br />Search for users to start
                        chatting!
                    </div>
                {/if}

                {#each chats as chat (chat.id)}
                    <button
                        onclick={() => selectChat(chat.id)}
                        class="w-full flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors border-b border-gray-50 text-left {activeChatId ===
                        chat.id
                            ? 'bg-blue-50 border-l-4 border-l-blue-600'
                            : 'border-l-4 border-l-transparent'}"
                    >
                        <div
                            class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-lg border-2 border-white shadow-sm"
                        >
                            {chat.title?.charAt(0) || "C"}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div
                                class="flex justify-between items-baseline mb-1"
                            >
                                <h3
                                    class="text-sm font-bold text-gray-800 truncate"
                                >
                                    {chat.title}
                                    {#if chat.unread > 0}
                                        <span
                                            class="ml-1 bg-red-400 text-white text-xs px-1 rounded-full"
                                            >{chat.unread}</span
                                        >
                                    {/if}
                                </h3>
                                <span class="text-[10px] text-gray-400"
                                    >{chat.time || ""}</span
                                >
                            </div>
                            <div class="flex justify-between items-center">
                                <p class="text-xs text-gray-500 truncate">
                                    {chat.last_message || "No messages"}
                                </p>
                            </div>
                        </div>
                    </button>
                {/each}
            {/if}
        </div>
    </aside>

    <!-- Main Chat Content -->
    <main class="flex-1 flex flex-col relative bg-gray-50">
        <div class="absolute inset-0">
            {#if activeChatId}
                <Chat chatId={activeChatId} {user} />
            {:else}
                <div
                    class="h-full flex flex-col items-center justify-center text-gray-400 bg-white"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-16 w-16 mb-4 opacity-10"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        />
                    </svg>
                    <p class="text-lg font-medium">
                        Select a chat to start messaging
                    </p>
                </div>
            {/if}
        </div>
    </main>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
</style>
