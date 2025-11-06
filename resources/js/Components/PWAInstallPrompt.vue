<template>
    <div>
        <!-- Install Prompt -->
        <div v-if="showInstallPrompt" class="fixed bottom-4 right-4 z-50 max-w-sm">
            <div class="bg-white rounded-lg shadow-xl p-4 border-2 border-blue-500">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 mb-1">Install App</h3>
                        <p class="text-sm text-gray-600 mb-3">Get daily notifications and offline access</p>
                        <div class="flex gap-2">
                            <button @click="installPWA"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                                Install
                            </button>
                            <button @click="dismissInstall"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                                Later
                            </button>
                        </div>
                    </div>
                    <button @click="dismissInstall" class="text-gray-400 hover:text-gray-600">
                        ×
                    </button>
                </div>
            </div>
        </div>

        <!-- Notification Prompt (shows after install OR on first visit) -->
        <div v-if="showNotificationPrompt" class="fixed bottom-4 left-4 z-50 max-w-sm">
            <div class="bg-white rounded-lg shadow-xl p-4 border-2 border-orange-500">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 mb-1">Daily Reports</h3>
                        <p class="text-sm text-gray-600 mb-3">Get notified every day at 8 AM with material status</p>
                        <div class="flex gap-2">
                            <button @click="enableNotifications"
                                class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                                Enable
                            </button>
                            <button @click="dismissNotification"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                                Skip
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { initPushNotifications } from '@/notifications'

const showInstallPrompt = ref(false)
const showNotificationPrompt = ref(false)
let deferredPrompt = null

onMounted(() => {
    // Check if already dismissed
    const installDismissed = localStorage.getItem('install-dismissed')
    const notificationDismissed = localStorage.getItem('notification-dismissed')

    // PWA Install Prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault()
        deferredPrompt = e
        if (!installDismissed) {
            showInstallPrompt.value = true
        }
    })

    // Check if app is installed
    const isInstalled = window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true

    if (isInstalled) {
        console.log('App is installed')
    }

    // Show notification prompt if not subscribed and not dismissed
    const isSubscribed = localStorage.getItem('push-subscribed')
    if (!isSubscribed && !notificationDismissed) {
        setTimeout(() => {
            showNotificationPrompt.value = true
        }, 3000) // Wait 3 seconds before showing
    }
})

const installPWA = async () => {
    if (!deferredPrompt) return

    deferredPrompt.prompt()
    const { outcome } = await deferredPrompt.userChoice

    if (outcome === 'accepted') {
        console.log('User accepted PWA install')
        showNotificationPrompt.value = true // Show notification prompt after install
    }

    deferredPrompt = null
    showInstallPrompt.value = false
}

const dismissInstall = () => {
    showInstallPrompt.value = false
    localStorage.setItem('install-dismissed', 'true')
}

const enableNotifications = async () => {
    const success = await initPushNotifications()
    if (success) {
        showNotificationPrompt.value = false
        alert('✅ Notifications enabled! You\'ll receive daily reports at 8 AM.')
    } else {
        alert('❌ Failed to enable notifications. Please check browser settings.')
    }
}

const dismissNotification = () => {
    showNotificationPrompt.value = false
    localStorage.setItem('notification-dismissed', 'true')
}
</script>
