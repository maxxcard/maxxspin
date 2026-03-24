<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

type WheelSegment = {
  label: string
  color: string
  chance: number
}

type ApiWheelSegment = {
  id: number
  name: string
  color: string
  chance: number
}

const segmentsApiKey = 'wheel-segments-key'
const segments = ref<WheelSegment[]>([])
const baseRotation = ref(0)
const spinning = ref(false)
const activeIndex = ref<number | null>(null)
const loading = ref(true)
const loadError = ref('')
const wheelSize = 760
const center = wheelSize / 2
const outerRadius = 340
const innerRadius = 104
const labelRadius = 242
const segmentAngle = computed(() => {
  return segments.value.length > 0 ? 360 / segments.value.length : 0
})

const buttonLabel = computed(() => {
  if (spinning.value) {
    return 'Spinning...'
  }

  if (loading.value) {
    return 'Loading...'
  }

  if (loadError.value) {
    return 'Unavailable'
  }

  if (activeIndex.value === null) {
    return 'Spin the Wheel'
  }

  return `Spin Again`
})

const indicatorLabel = computed(() => {
  if (spinning.value) {
    return 'Spinning'
  }

  if (loading.value) {
    return 'Loading'
  }

  if (loadError.value) {
    return 'Error'
  }

  if (activeIndex.value === null) {
    return 'Spin'
  }

  return segments.value[activeIndex.value]?.label ?? 'Spin'
})

const sliceGeometry = computed(() => {
  return segments.value.map((segment, index) => {
    const startAngle = -90 + index * segmentAngle.value
    const endAngle = startAngle + segmentAngle.value
    const midAngle = startAngle + segmentAngle.value / 2
    const start = polarToCartesian(outerRadius, startAngle)
    const end = polarToCartesian(outerRadius, endAngle)
    const largeArcFlag = segmentAngle.value > 180 ? 1 : 0
    const labelPoint = polarToCartesian(labelRadius, midAngle)

    return {
      ...segment,
      path: [
        `M ${center} ${center}`,
        `L ${start.x} ${start.y}`,
        `A ${outerRadius} ${outerRadius} 0 ${largeArcFlag} 1 ${end.x} ${end.y}`,
        'Z',
      ].join(' '),
      labelX: labelPoint.x,
      labelY: labelPoint.y,
      labelRotation: midAngle + 90,
    }
  })
})

function polarToCartesian(radius: number, angleInDegrees: number) {
  const radians = (angleInDegrees * Math.PI) / 180

  return {
    x: center + radius * Math.cos(radians),
    y: center + radius * Math.sin(radians),
  }
}

function pickSegmentIndexByChance() {
  const weightedSegments = segments.value.flatMap((segment, index) => {
    return Array.from({ length: segment.chance }, () => index)
  })

  if (weightedSegments.length === 0) {
    throw new Error('Wheel segment chances must be greater than 0.')
  }

  const weightedIndex = Math.floor(Math.random() * weightedSegments.length)

  return weightedSegments[weightedIndex]
}

function spinWheel() {
  if (spinning.value || loading.value || loadError.value || segments.value.length === 0) {
    return
  }

  const nextIndex = pickSegmentIndexByChance()
  const fullSpins = 5
  const pointerAngle = -90
  const segmentCenter = -90 + nextIndex * segmentAngle.value + segmentAngle.value / 2
  const currentRotation = ((baseRotation.value % 360) + 360) % 360
  const targetRotation =
    fullSpins * 360 + (pointerAngle - (currentRotation + segmentCenter) + 360) % 360

  spinning.value = true
  activeIndex.value = null
  baseRotation.value += targetRotation

  window.setTimeout(() => {
    activeIndex.value = nextIndex
    spinning.value = false
  }, 4800)
}

async function loadSegments() {
  loading.value = true
  loadError.value = ''

  try {
    const response = await fetch('/api/segments.json', {
      headers: {
        'X-Wheel-Key': segmentsApiKey,
      },
    })

    if (!response.ok) {
      throw new Error(`Failed to load wheel options. HTTP ${response.status}`)
    }

    const data = (await response.json()) as ApiWheelSegment[]
    const nextSegments = data.map((segment) => ({
      label: segment.name,
      color: segment.color,
      chance: Number(segment.chance),
    }))
    const totalChance = nextSegments.reduce((total, segment) => total + segment.chance, 0)

    if (nextSegments.length === 0) {
      throw new Error('No wheel options were returned by the API.')
    }

    if (totalChance !== 100) {
      throw new Error(`Wheel segment chances must total 100. Received ${totalChance}.`)
    }

    segments.value = nextSegments
    activeIndex.value = null
  } catch (error) {
    loadError.value = error instanceof Error ? error.message : 'Failed to load wheel options.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadSegments()
})
</script>

<template>
  <main class="relative isolate grid min-h-screen place-items-center overflow-hidden px-6 py-8">
    <div class="absolute inset-0 -z-20 bg-[linear-gradient(180deg,#0f172a_0%,#111827_45%,#020617_100%)]"></div>
    <div
      class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_50%_18%,rgba(255,255,255,0.12),transparent_18%),radial-gradient(circle_at_20%_80%,rgba(249,115,22,0.12),transparent_22%),radial-gradient(circle_at_82%_24%,rgba(59,130,246,0.14),transparent_20%)]">
    </div>

    <section class="flex w-full max-w-6xl flex-col items-center gap-10">
      <p v-if="loadError"
        class="rounded-full border border-rose-200/30 bg-rose-500/10 px-4 py-2 text-sm font-medium text-rose-100">
        {{ loadError }}
      </p>

      <div class="relative flex w-full items-center justify-center">
        <div id="indicator"
          class="absolute top-1/2 z-30 flex -translate-y-[18.9rem] flex-col items-center sm:-translate-y-[22.2rem] md:-translate-y-[25.6rem]">
          <div
            class="rounded-full border border-white/12 bg-slate-950/82 px-4 py-1.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-100 shadow-[0_12px_26px_rgba(2,6,23,0.4)] backdrop-blur-md">
            {{ indicatorLabel }}
          </div>
          <div class="flex flex-col items-center">
            <div class="h-2 w-px bg-white/35"></div>
            <div
              class="h-3.5 w-3.5 rounded-full border border-white/60 bg-slate-950 shadow-[0_0_0_4px_rgba(15,23,42,0.42)]">
            </div>
            <div
              class="h-0 w-0 border-x-14 border-t-22 border-x-transparent border-t-white drop-shadow-[0_8px_12px_rgba(15,23,42,0.3)]">
            </div>
          </div>
        </div>

        <div
          class="absolute h-100 w-100 rounded-full bg-[radial-gradient(circle,rgba(255,255,255,0.18),transparent_60%)] blur-3xl sm:h-124 sm:w-124 md:h-144 md:w-xl">
        </div>

        <div
          class="relative rounded-4xl border border-white/10 bg-white/5 p-4 shadow-[0_30px_120px_rgba(2,6,23,0.55)] backdrop-blur-2xl sm:p-6">
          <div
            class="relative h-104 w-104 transition-transform duration-4800 ease-[cubic-bezier(0.16,1,0.3,1)] sm:h-128 sm:w-lg md:h-148 md:w-148"
            :style="{ transform: `rotate(${baseRotation}deg)` }">
            <svg class="h-full w-full drop-shadow-[0_35px_80px_rgba(15,23,42,0.4)]" viewBox="0 0 760 760" fill="none"
              xmlns="http://www.w3.org/2000/svg">
              <circle cx="380" cy="380" r="360" fill="rgba(15, 23, 42, 0.72)" />
              <circle cx="380" cy="380" r="348" fill="#0f172a" stroke="rgba(255,255,255,0.1)" stroke-width="2" />

              <path d="M380 32 A348 348 0 1 1 379.9 32" stroke="rgba(255,255,255,0.16)" stroke-width="24" />

              <g v-for="slice in sliceGeometry" :key="slice.label">
                <path :d="slice.path" :fill="slice.color" stroke="rgba(255,255,255,0.22)" stroke-width="3" />
                <path :d="slice.path" fill="url(#sliceGloss)" opacity="0.2" />
                <g :transform="`translate(${slice.labelX} ${slice.labelY}) rotate(${slice.labelRotation})`">
                  <rect x="-78" y="-22" width="156" height="44" rx="22" fill="rgba(255,255,255,0.14)"
                    stroke="rgba(255,255,255,0.24)" />
                  <text text-anchor="middle" dominant-baseline="middle" fill="rgba(255,255,255,0.98)" font-size="22"
                    font-weight="700" letter-spacing="0.4">
                    {{ slice.label }}
                  </text>
                </g>
              </g>

              <circle cx="380" cy="380" r="138" fill="rgba(15, 23, 42, 0.9)" stroke="rgba(255,255,255,0.14)"
                stroke-width="5" />
              <circle cx="380" cy="380" r="94" fill="url(#centerCore)" />
              <circle cx="380" cy="380" r="22" fill="#fff7ed" opacity="0.95" />

              <defs>
                <radialGradient id="centerCore" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse"
                  gradientTransform="translate(346 330) rotate(53.4) scale(196.321)">
                  <stop stop-color="#fef3c7" />
                  <stop offset="0.48" stop-color="#f59e0b" />
                  <stop offset="1" stop-color="#ea580c" />
                </radialGradient>
                <linearGradient id="sliceGloss" x1="150" y1="110" x2="650" y2="650" gradientUnits="userSpaceOnUse">
                  <stop stop-color="white" />
                  <stop offset="1" stop-color="white" stop-opacity="0" />
                </linearGradient>
              </defs>
            </svg>
          </div>
        </div>
      </div>

      <button type="button"
        class="inline-flex min-w-64 items-center justify-center rounded-full border border-white/15 bg-[linear-gradient(135deg,#fff7ed_0%,#fdba74_25%,#f97316_100%)] px-10 py-4 text-base font-black uppercase tracking-[0.24em] text-slate-950 shadow-[0_22px_55px_rgba(249,115,22,0.35)] transition duration-200 hover:scale-[1.02] hover:shadow-[0_28px_70px_rgba(249,115,22,0.45)] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:scale-100"
        :disabled="spinning || loading || !!loadError || segments.length === 0" @click="spinWheel">
        {{ buttonLabel }}
      </button>
    </section>
  </main>
</template>
