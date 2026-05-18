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
const submitting = ref(false)
const activeIndex = ref<number | null>(null)
const loading = ref(true)
const loadError = ref('')
const submitError = ref('')
const formData = ref({
  firstName: '',
  lastName: '',
  email: '',
  company: '',
  cargo: '',
  phone: '',
})
const wheelSize = 760
const center = wheelSize / 2
const outerRadius = 340
const labelRadius = 242
const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const isFormComplete = computed(() => {
  return Object.values(formData.value).every((value) => value.trim().length > 0)
})
const phoneDigits = computed(() => {
  return formData.value.phone.replace(/\D/g, '')
})
const isEmailValid = computed(() => {
  return emailPattern.test(formData.value.email.trim())
})
const isPhoneValid = computed(() => {
  return phoneDigits.value.length === 11
})
const showEmailError = computed(() => {
  return formData.value.email.trim().length > 0 && !isEmailValid.value
})
const showPhoneError = computed(() => {
  return phoneDigits.value.length > 0 && !isPhoneValid.value
})
const isFormValid = computed(() => {
  return isFormComplete.value && isEmailValid.value && isPhoneValid.value
})
const isSpinDisabled = computed(() => {
  return (
    spinning.value ||
    submitting.value ||
    loading.value ||
    !!loadError.value ||
    segments.value.length === 0 ||
    !isFormValid.value
  )
})
const segmentAngle = computed(() => {
  return segments.value.length > 0 ? 360 / segments.value.length : 0
})

const buttonLabel = computed(() => {
  if (spinning.value) {
    return 'Spinning...'
  }

  if (submitting.value) {
    return 'Enviando...'
  }

  if (loading.value) {
    return 'Loading...'
  }

  if (loadError.value) {
    return 'Unavailable'
  }

  if (!isFormComplete.value) {
    return 'Preencha o formulário'
  }

  if (!isFormValid.value) {
    return 'Corrija os campos'
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
  const selectedSegment = weightedSegments[weightedIndex]

  if (selectedSegment === undefined) {
    throw new Error('Failed to select a wheel segment.')
  }

  return selectedSegment
}

function formatBrazilianPhone(value: string) {
  const digits = value.replace(/\D/g, '').slice(0, 11)

  if (digits.length <= 2) {
    return digits
  }

  if (digits.length <= 6) {
    return `(${digits.slice(0, 2)}) ${digits.slice(2)}`
  }

  if (digits.length <= 10) {
    return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`
  }

  return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`
}

function handlePhoneInput(event: Event) {
  const target = event.target as HTMLInputElement | null

  if (!target) {
    return
  }

  formData.value.phone = formatBrazilianPhone(target.value)
  target.value = formData.value.phone
}

function handlePhoneKeydown(event: KeyboardEvent) {
  const allowedKeys = [
    'Backspace',
    'Delete',
    'ArrowLeft',
    'ArrowRight',
    'ArrowUp',
    'ArrowDown',
    'Tab',
    'Home',
    'End',
  ]

  if (event.ctrlKey || event.metaKey) {
    return
  }

  if (allowedKeys.includes(event.key)) {
    return
  }

  if (!/^\d$/.test(event.key)) {
    event.preventDefault()
  }
}

function resetFormData() {
  formData.value = {
    firstName: '',
    lastName: '',
    email: '',
    company: '',
    cargo: '',
    phone: '',
  }
}

async function sendFormData() {
  const payload = {
    firstName: formData.value.firstName.trim(),
    lastName: formData.value.lastName.trim(),
    name: `${formData.value.firstName.trim()} ${formData.value.lastName.trim()}`.trim(),
    email: formData.value.email.trim(),
    company: formData.value.company.trim(),
    cargo: formData.value.cargo.trim(),
    phone: phoneDigits.value,
  }

  const response = await fetch('/api/send-data', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Wheel-Key': segmentsApiKey,
    },
    body: JSON.stringify(payload),
  })

  if (!response.ok) {
    throw new Error(`Falha ao enviar os dados. HTTP ${response.status}`)
  }
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

async function handleSpin() {
  if (isSpinDisabled.value) {
    return
  }

  submitError.value = ''
  submitting.value = true

  try {
    await sendFormData()
    resetFormData()
    spinWheel()
  } catch (error) {
    submitError.value = error instanceof Error ? error.message : 'Falha ao enviar os dados do formulario.'
  } finally {
    submitting.value = false
  }
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

    <section class="flex w-full max-w-6xl flex-col items-center gap-8">
      <p v-if="loadError"
        class="rounded-full border border-rose-200/30 bg-rose-500/10 px-4 py-2 text-sm font-medium text-rose-100">
        {{ loadError }}
      </p>

      <div class="flex w-full flex-col items-center justify-center gap-6 lg:flex-row lg:items-center lg:gap-10">
        <div class="relative flex items-center justify-center">
          <div id="indicator"
            class="absolute top-1/2 z-30 flex -translate-y-[14.8rem] flex-col items-center sm:-translate-y-70 md:-translate-y-[20.2rem]">
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
            class="absolute h-88 w-88 rounded-full bg-[radial-gradient(circle,rgba(255,255,255,0.18),transparent_60%)] blur-3xl sm:h-112 sm:w-md md:h-128 md:w-lg">
          </div>

          <div
            class="relative rounded-4xl border border-white/10 bg-white/5 p-4 shadow-[0_30px_120px_rgba(2,6,23,0.55)] backdrop-blur-2xl sm:p-6">
            <div
              class="relative h-92 w-92 transition-transform duration-4800 ease-[cubic-bezier(0.16,1,0.3,1)] sm:h-116 sm:w-116 md:h-136 md:w-136"
              :style="{ transform: `rotate(${baseRotation}deg)` }">
              <svg class="h-full w-full drop-shadow-[0_35px_80px_rgba(15,23,42,0.4)]" viewBox="0 0 760 760" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <circle cx="380" cy="380" r="360" fill="rgba(15, 23, 42, 0.72)" />
                <circle cx="380" cy="380" r="348" fill="#0f172a" stroke="rgba(255,255,255,0.1)" stroke-width="2" />

                <path d="M380 32 A348 348 0 1 1 379.9 32" stroke="rgba(255,255,255,0.16)" stroke-width="24" />

                <g v-for="slice in sliceGeometry" :key="slice.label">
                  <path :d="slice.path" :fill="slice.color" stroke="rgba(255,255,255,0.22)" stroke-width="3" />
                  <g :transform="`translate(${slice.labelX} ${slice.labelY}) rotate(${slice.labelRotation})`">
                    <text text-anchor="middle" dominant-baseline="middle" fill="rgba(255,255,255,0.98)" font-size="22"
                      font-weight="700" letter-spacing="0.4" style="paint-order: stroke; stroke: rgba(15,23,42,0.42); stroke-width: 8px; stroke-linejoin: round;">
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
                </defs>
              </svg>
            </div>
          </div>
        </div>

        <div
          class="flex w-full max-w-md flex-col gap-5 rounded-4xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_90px_rgba(2,6,23,0.38)] backdrop-blur-2xl">

          <div class="grid gap-4">
            <label class="flex flex-col gap-2 text-sm font-medium text-slate-200">
              <span>Nome</span>
              <input v-model="formData.firstName" type="text" autocomplete="given-name" placeholder="Seu nome"
                class="rounded-2xl border border-white/12 bg-slate-950/60 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-orange-300/70 focus:bg-slate-950" />
            </label>

            <label class="flex flex-col gap-2 text-sm font-medium text-slate-200">
              <span>Sobrenome</span>
              <input v-model="formData.lastName" type="text" autocomplete="family-name" placeholder="Seu sobrenome"
                class="rounded-2xl border border-white/12 bg-slate-950/60 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-orange-300/70 focus:bg-slate-950" />
            </label>

            <label class="flex flex-col gap-2 text-sm font-medium text-slate-200">
              <span>E-mail coorporativo</span>
              <input v-model="formData.email" type="email" autocomplete="email" placeholder="voce@empresa.com"
                :class="showEmailError ? 'border-rose-400/80 focus:border-rose-300/90' : 'border-white/12 focus:border-orange-300/70'"
                class="rounded-2xl bg-slate-950/60 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:bg-slate-950" />
              <span v-if="showEmailError" class="text-xs font-medium text-rose-300">
                Digite um e-mail valido.
              </span>
            </label>

            <label class="flex flex-col gap-2 text-sm font-medium text-slate-200">
              <span>Empresa</span>
              <input v-model="formData.company" type="text" autocomplete="organization" placeholder="Nome da empresa"
                class="rounded-2xl border border-white/12 bg-slate-950/60 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-orange-300/70 focus:bg-slate-950" />
            </label>

            <label class="flex flex-col gap-2 text-sm font-medium text-slate-200">
              <span>Cargo</span>
              <input v-model="formData.cargo" type="text" autocomplete="organization-title" placeholder="Seu cargo"
                class="rounded-2xl border border-white/12 bg-slate-950/60 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-orange-300/70 focus:bg-slate-950" />
            </label>

            <label class="flex flex-col gap-2 text-sm font-medium text-slate-200">
              <span>Telefone</span>
              <input :value="formData.phone" type="tel" inputmode="numeric" autocomplete="tel"
                placeholder="(11) 98765-4321" @input="handlePhoneInput" @keydown="handlePhoneKeydown"
                :class="showPhoneError ? 'border-rose-400/80 focus:border-rose-300/90' : 'border-white/12 focus:border-orange-300/70'"
                class="rounded-2xl bg-slate-950/60 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:bg-slate-950" />
              <span v-if="showPhoneError" class="text-xs font-medium text-rose-300">
                Digite um telefone brasileiro com 11 numeros.
              </span>
            </label>
          </div>

          <button type="button"
            class="inline-flex w-full items-center justify-center rounded-full border border-white/15 bg-[linear-gradient(135deg,#fff7ed_0%,#fdba74_25%,#f97316_100%)] px-10 py-4 text-base font-black uppercase tracking-[0.24em] text-slate-950 shadow-[0_22px_55px_rgba(249,115,22,0.35)] transition duration-200 hover:scale-[1.02] hover:shadow-[0_28px_70px_rgba(249,115,22,0.45)] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:scale-100"
            :disabled="isSpinDisabled" @click="handleSpin">
            {{ buttonLabel }}
          </button>

          <p v-if="submitError" class="text-sm font-medium text-rose-300">
            {{ submitError }}
          </p>
        </div>
      </div>
    </section>
  </main>
</template>
