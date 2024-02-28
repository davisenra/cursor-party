import { createApp, ref, onMounted } from "vue";

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
    const onlineCursors = ref([]);
    let ws;

    onMounted(() => {
      ws = new WebSocket("wss://cursor-party-ws.fly.dev/");

      ws.addEventListener("open", () => {
        console.log("WebSocket connection established");
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
    });

    return { onlineCursors };
  },
});

app.mount("#app");
