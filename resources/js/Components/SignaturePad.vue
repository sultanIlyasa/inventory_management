<template>
    <div class="flex flex-col gap-2">
        <label v-if="label" class="text-sm font-medium text-gray-700">{{ label }}</label>
        <div class="relative border border-gray-300 rounded-lg overflow-hidden bg-white">
            <canvas
                ref="canvasRef"
                class="w-full touch-none"
                :style="{ height: height + 'px' }"
            />
            <button
                type="button"
                @click="clear"
                class="absolute top-2 right-2 px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 rounded transition-colors"
            >
                Clear
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import SignaturePadLib from 'signature_pad';

const props = defineProps({
    label: {
        type: String,
        default: '',
    },
    height: {
        type: Number,
        default: 200,
    },
});

const emit = defineEmits(['update:signature']);

const canvasRef = ref(null);
let signaturePad = null;
let resizeObserver = null;

const resizeCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();

    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;

    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);

    if (signaturePad) {
        signaturePad.clear();
    }
};

const emitSignature = () => {
    if (signaturePad && !signaturePad.isEmpty()) {
        emit('update:signature', signaturePad.toDataURL('image/png'));
    } else {
        emit('update:signature', '');
    }
};

const isEmpty = () => {
    return signaturePad ? signaturePad.isEmpty() : true;
};

const clear = () => {
    if (signaturePad) {
        signaturePad.clear();
    }
    emit('update:signature', '');
};

defineExpose({ isEmpty, clear });

onMounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    resizeCanvas();

    signaturePad = new SignaturePadLib(canvas, {
        backgroundColor: 'rgba(0, 0, 0, 0)',
    });

    signaturePad.addEventListener('endStroke', emitSignature);

    resizeObserver = new ResizeObserver(() => {
        resizeCanvas();
    });
    resizeObserver.observe(canvas.parentElement);
});

onUnmounted(() => {
    if (signaturePad) {
        signaturePad.off();
    }
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});
</script>
