import { createApp, ref, computed } from "vue";

function throttle(func, delay) {
  let lastCall = 0;
  return function (...args) {
    const now = new Date().getTime();
    if (now - lastCall >= delay) {
      lastCall = now;
      return func(...args);
    }
  };
}

const app = createApp({
  setup() {
    const onlineCursors = ref({});
    const onlineCursorsCount = computed(() => {
      if (!onlineCursors.value) return null;
      return Object.keys(onlineCursors.value).length + 1;
    });
    const isConnected = ref(false);
    const isConnecting = ref(false);
    const username = ref("");

    function joinWebsocket() {
      isConnecting.value = true;
      const ws = new WebSocket(`ws://localhost:9502?username=${username.value}`);

      ws.addEventListener("open", () => {
        isConnecting.value = false;
        isConnected.value = true;
      });

      ws.addEventListener("message", (message) => {
        const { cursors } = JSON.parse(message.data);
        onlineCursors.value = cursors;
      });

      window.addEventListener(
        "mousemove",
        throttle((ev) => {
          const websocketConnectionIsOpen = ws.readyState === 1;
          if (!websocketConnectionIsOpen) {
            return;
          }
          ws.send(JSON.stringify({ x: ev.clientX, y: ev.clientY }));
        }, 50)
      );
    }

    return {
      onlineCursors,
      onlineCursorsCount,
      isConnected,
      isConnecting,
      username,
      joinWebsocket,
    };
  },
});

app.mount("#app");
