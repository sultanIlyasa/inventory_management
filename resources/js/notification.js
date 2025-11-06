export async function initPushNotifications() {
    // Check browser support
    if (!("Notification" in window)) {
        console.log("This browser does not support notifications");
        return false;
    }

    if (!("serviceWorker" in navigator)) {
        console.log("Service Worker not supported");
        return false;
    }

    // Request permission
    const permission = await Notification.requestPermission();

    if (permission !== "granted") {
        console.log("Notification permission denied");
        return false;
    }

    try {
        // Wait for service worker to be ready
        const registration = await navigator.serviceWorker.ready;

        // Check if already subscribed
        let subscription = await registration.pushManager.getSubscription();

        // If not subscribed, create new subscription
        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(
                    import.meta.env.VITE_VAPID_PUBLIC_KEY
                ),
            });
        }

        // Send subscription to backend (NO auth token needed!)
        const response = await fetch("/api/push-subscribe", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
            body: JSON.stringify(subscription.toJSON()),
        });

        const data = await response.json();

        if (data.success) {
            console.log("Successfully subscribed to push notifications");
            // Store subscription status in localStorage
            localStorage.setItem("push-subscribed", "true");
            return true;
        } else {
            console.error("Failed to save subscription to server");
            return false;
        }
    } catch (error) {
        console.error("Failed to subscribe to push notifications:", error);
        return false;
    }
}

export async function unsubscribePushNotifications() {
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            // Unsubscribe from browser
            await subscription.unsubscribe();

            // Tell backend to remove subscription
            await fetch("/api/push-unsubscribe", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify(subscription.toJSON()),
            });

            localStorage.removeItem("push-subscribed");
            return true;
        }
    } catch (error) {
        console.error("Failed to unsubscribe:", error);
        return false;
    }
}

// Helper function
function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, "+")
        .replace(/_/g, "/");

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
