<script>
    import { onMount, tick } from "svelte";
    import axios from "axios";

    let { chatId, user } = $props();

    let messages = $state([]);
    let chatData = $state(null);
    let newMessage = $state("");
    let chatContainer = $state();
    let loading = $state(false);

    // Derived values for UI
    let displayTitle = $derived.by(() => {
        if (!chatData) return "Loading...";
        const otherUser = chatData.users?.find((u) => u.id !== user?.id);
        return otherUser?.nickname || "Unknown User";
    });

    // Scroll to bottom when messages update
    $effect(() => {
        if (messages.length && chatContainer) {
            tick().then(() => {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            });
        }
    });

    // Handle chatId changes: fetch data and setup Echo
    $effect(() => {
        if (!chatId) return;

        fetchChatData();

        window.Echo.private(`user.${user.id}.chats`).listen(
            ".MessageSent",
            (e) => {
                const message = e.message;
                if (
                    message.chat_id === chatId &&
                    !messages.find((m) => m.id === message.id)
                ) {
                    messages = [
                        ...messages,
                        {
                            ...message,
                            is_me: message.user_id === user?.id,
                        },
                    ];
                }
            },
        );
    });

    async function fetchChatData() {
        loading = true;
        try {
            const response = await axios.get(`/chats/${chatId}`);
            chatData = response.data;

            const msgsResponse = await axios.get(`/chats/${chatId}/messages`);
            messages = msgsResponse.data.map((m) => ({
                ...m,
                is_me: m.user_id === user?.id,
            }));
        } catch (error) {
            console.error("Failed to fetch chat data:", error);
        } finally {
            loading = false;
        }
    }

    async function sendMessage() {
        if (newMessage.trim() === "" || !chatId) return;

        const messageText = newMessage;
        const tempId = "temp-" + Date.now();

        newMessage = "";

        try {
            // Optimistic update
            const myMessage = {
                id: tempId,
                text: messageText,
                user: { nickname: user.nickname },
                user_id: user.id,
                is_me: true,
            };
            messages = [...messages, myMessage];

            // Send to Laravel backend
            const response = await axios.post("/messages", {
                chat_id: chatId,
                text: messageText,
            });

            // Replace temp message with server version
            if (response.data && response.data.message) {
                messages = messages.map((m) =>
                    m.id === tempId
                        ? { ...response.data.message, is_me: true }
                        : m,
                );
            }
        } catch (error) {
            console.error("Failed to send message:", error);
            // Remove the optimistic message on failure
            messages = messages.filter((m) => m.id !== tempId);
        }
    }

    function handleKeydown(event) {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    }
</script>

<div class="flex flex-col h-full w-full bg-white">
    <!-- Header -->
    <div
        class="p-4 border-b bg-white flex items-center justify-between shadow-sm z-10"
    >
        <div class="flex items-center gap-3">
            <div
                class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold shadow-sm"
            >
                {displayTitle.charAt(0).toUpperCase()}
            </div>
            <div>
                <h1 class="text-sm font-bold text-gray-800">
                    {displayTitle}
                </h1>
                <span
                    class="text-[10px] text-green-500 font-bold uppercase tracking-wider"
                    >Active</span
                >
            </div>
        </div>
    </div>

    <!-- Messages Area -->
    <div
        bind:this={chatContainer}
        class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 scroll-smooth custom-scrollbar"
    >
        {#if loading && messages.length === 0}
            <div class="flex items-center justify-center h-full">
                <div
                    class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
                ></div>
            </div>
        {:else if messages.length === 0}
            <div
                class="flex flex-col items-center justify-center h-full text-gray-400 space-y-2"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-12 w-12 opacity-20"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                    />
                </svg>
                <p>No messages yet. Say hello!</p>
            </div>
        {/if}

        {#each messages as msg (msg.id)}
            <div class="flex {msg.is_me ? 'justify-end' : 'justify-start'}">
                <div class="max-w-[85%] group">
                    {#if !msg.is_me}
                        <p class="text-[10px] text-gray-500 mb-1 px-1">
                            {msg.user?.nickname || "User"}
                        </p>
                    {/if}
                    <div
                        class="rounded-2xl px-4 py-2 {msg.is_me
                            ? 'bg-blue-600 text-white rounded shadow-md shadow-blue-100'
                            : 'bg-white text-gray-800 shadow-sm border border-gray-100 rounded'}"
                    >
                        <p class="text-sm leading-relaxed">{msg.text}</p>
                    </div>
                </div>
            </div>
        {/each}
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-white border-t">
        <div
            class="flex items-center gap-3 bg-gray-100 rounded-2xl px-4 py-1 focus-within:ring-2 focus-within:ring-blue-500 focus-within:bg-white transition-all"
        >
            <input
                type="text"
                bind:value={newMessage}
                onkeydown={handleKeydown}
                placeholder="Type your message..."
                class="flex-1 py-3 bg-transparent outline-none text-sm text-gray-700"
            />
            <button
                onclick={sendMessage}
                title="Send Message"
                disabled={!newMessage.trim() || !chatId}
                class="text-blue-600 hover:text-blue-800 disabled:opacity-30 transition-opacity p-2"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 rotate-90"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                    />
                </svg>
            </button>
        </div>
    </div>
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
