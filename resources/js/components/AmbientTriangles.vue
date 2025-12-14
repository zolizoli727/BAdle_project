<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

type Triangle = {
  points: string
  delay: string
  duration: string
  driftX: string
  driftY: string
  rotate: string
  scaleFrom: string
  scaleTo: string
  opacity: number
  fill: string
}

interface Props {
  count?: number
  seed?: number
  xPadLeftPct?: number
  xPadRightPct?: number
  forceMotion?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  count: 40,
  seed: 42,
  xPadLeftPct: 0,
  xPadRightPct: 0,
  forceMotion: false,
})

const scale = ref(1)
const hasExternalScale = computed(() => Math.abs(scale.value - 1) > 0.05)
const disableMotion = computed(() => !props.forceMotion && hasExternalScale.value)

function computeScale() {
  if (typeof window === 'undefined') return 1

  const candidates: number[] = []

  const viewportScale = window.visualViewport?.scale
  if (viewportScale && isFinite(viewportScale)) candidates.push(viewportScale)

  const outerInner = window.innerWidth ? window.outerWidth / window.innerWidth : 1
  if (outerInner && isFinite(outerInner)) {
    candidates.push(outerInner)
    candidates.push(1 / outerInner)
  }

  const doc = document.documentElement
  const rectWidth = doc.getBoundingClientRect?.().width ?? doc.clientWidth
  if (rectWidth) {
    const cssZoom = window.innerWidth / rectWidth
    if (cssZoom && isFinite(cssZoom)) {
      candidates.push(cssZoom)
      candidates.push(1 / cssZoom)
    }
  }

  const deviceRatio = window.devicePixelRatio
  if (deviceRatio && isFinite(deviceRatio)) {
    candidates.push(deviceRatio)
    candidates.push(1 / deviceRatio)
  }

  if (!candidates.length) return 1

  let best = 1
  let maxDelta = 0
  for (const value of candidates) {
    if (value <= 0) continue
    const delta = Math.abs(value - 1)
    if (delta > maxDelta) {
      maxDelta = delta
      best = value
    }
  }

  return maxDelta < 0.01 ? 1 : Number(best.toFixed(3))
}

function updateScale() {
  scale.value = computeScale()
}

let resizeHandler: (() => void) | null = null
let docObserver: ResizeObserver | null = null

onMounted(() => {
  updateScale()

  if (typeof window === 'undefined') return

  resizeHandler = () => updateScale()

  window.addEventListener('resize', resizeHandler)
  window.addEventListener('orientationchange', resizeHandler)

  if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', resizeHandler)
  }

  if (typeof ResizeObserver !== 'undefined') {
    docObserver = new ResizeObserver(() => updateScale())
    docObserver.observe(document.documentElement)
  }
})

onBeforeUnmount(() => {
  if (typeof window === 'undefined') return
  if (!resizeHandler) return

  window.removeEventListener('resize', resizeHandler)
  window.removeEventListener('orientationchange', resizeHandler)
  if (window.visualViewport) {
    window.visualViewport.removeEventListener('resize', resizeHandler)
  }
  resizeHandler = null

  if (docObserver) {
    docObserver.disconnect()
    docObserver = null
  }
})

function trianglePoints(cx: number, cy: number, size: number, rotationDeg: number): string {
  const r = size / Math.sqrt(3)
  const rot = (rotationDeg * Math.PI) / 180
  const pts: Array<[number, number]> = []
  for (let i = 0; i < 3; i++) {
    const a = rot + i * ((2 * Math.PI) / 3)
    const x = cx + r * Math.cos(a)
    const y = cy + r * Math.sin(a)
    pts.push([x, y])
  }
  return pts.map(([x, y]) => `${x.toFixed(2)},${y.toFixed(2)}`).join(' ')
}

function seededRandom(seed: number) {
  let s = seed >>> 0
  return () => {
    s = (1664525 * s + 1013904223) >>> 0
    return (s & 0xffffffff) / 0x100000000
  }
}

function halton(index: number, base: number) {
  let f = 1
  let r = 0
  let i = index
  while (i > 0) {
    f = f / base
    r = r + f * (i % base)
    i = Math.floor(i / base)
  }
  return r
}

function clamp01(v: number) {
  return v < 0 ? 0 : v > 1 ? 1 : v
}

const triangles = computed<Triangle[]>(() => {
  const rand = seededRandom(props.seed)
  const list: Triangle[] = []
  const count = props.count
  const margin = 5
  const xMin = margin + props.xPadLeftPct
  const xRange = Math.max(10, 100 - margin * 2 - props.xPadLeftPct - props.xPadRightPct)
  for (let i = 1; i <= count; i++) {
    const hx = halton(i, 2)
    const hy = halton(i, 3)
    const jx = (rand() - 0.5) * 0.06
    const jy = (rand() - 0.5) * 0.06
    const cx = xMin + clamp01(hx + jx) * xRange
    const cy = margin + clamp01(hy + jy) * (100 - margin * 2)
    const size = 6 + rand() * 12
    const rot = rand() * 360
    const delay = `${Math.floor(rand() * 8000)}ms`
    const duration = `${20000 + Math.floor(rand() * 24000)}ms`
    const driftX = `${(rand() * 6 - 3).toFixed(2)}px`
    const driftY = `${(rand() * 8 - 4).toFixed(2)}px`
    const scaleFrom = (0.92 + rand() * 0.06).toFixed(3)
    const scaleTo = (0.96 + rand() * 0.08).toFixed(3)
    const opacity = 0.4 + rand() * 0.2 // 0.4–0.6
    const hues = [200, 205, 210, 195, 190, 185]
    const hue = hues[Math.floor(rand() * hues.length)] + Math.floor(rand() * 10 - 5)
    const sat = 72 + Math.floor(rand() * 14)
    const light = 60 + Math.floor(rand() * 16)
    const fill = `hsl(${hue} ${sat}% ${light}%)`

    list.push({
      points: trianglePoints(cx, cy, size, rot),
      delay,
      duration,
      driftX,
      driftY,
      rotate: `${(rand() * 20 - 10).toFixed(2)}deg`,
      scaleFrom,
      scaleTo,
      opacity: Number(opacity.toFixed(3)),
      fill,
    })
  }
  return list
})

</script>

<template>
  <div aria-hidden="true" :class="[
    'ambient-triangles',
    'pointer-events-none',
    'absolute',
    'inset-0',
    'z-[1]',
    props.forceMotion ? 'force-motion' : '',
    disableMotion ? 'ambient-triangles--static' : ''
  ]">
    <svg viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice" class="w-full h-full">
      <g class="hue tris">
        <polygon v-for="(t, i) in triangles" :key="i" :points="t.points" :fill="t.fill" :opacity="t.opacity"
          stroke="white" :stroke-opacity="Math.min(0.35, t.opacity + 0.1)" stroke-width="0.15" class="tri" :style="{
            '--delay': t.delay,
            '--duration': t.duration,
            '--dx': t.driftX,
            '--dy': t.driftY,
            '--rot': t.rotate,
            '--scaleFrom': t.scaleFrom,
            '--scaleTo': t.scaleTo,
          } as any" />
      </g>


    </svg>
    <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/20"></div>
  </div>
</template>

<style scoped>
.ambient-triangles {
  opacity: var(--at-opacity, 0.85);
  mix-blend-mode: screen;
}

.ambient-triangles--static,
.ambient-triangles--static .hue,
.ambient-triangles--static .tri {
  animation: none !important;
}

.ambient-triangles--static .tri {
  opacity: 0.65;
}

.tri {
  transform-box: fill-box;
  transform-origin: center;
  animation: drift var(--duration) ease-in-out var(--delay) infinite alternate;
  filter: none;
}

.tris {
  will-change: filter;
}

@keyframes drift {
  0% {
    transform: translate(calc(var(--dx) * -1), var(--dy)) rotate(calc(var(--rot) * -1)) scale(var(--scaleFrom));
  }

  50% {
    transform: translate(calc(var(--dx) * 0.5), calc(var(--dy) * 0.5)) rotate(calc(var(--rot) * 0.5)) scale(calc((var(--scaleFrom) + var(--scaleTo)) / 2));
  }

  100% {
    transform: translate(var(--dx), calc(var(--dy) * -1)) rotate(var(--rot)) scale(var(--scaleTo));
  }
}

/* fade animation disabled */

/* hue animation removed */

@media (prefers-reduced-motion: reduce) {

  .ambient-triangles:not(.force-motion),
  .ambient-triangles:not(.force-motion) .hue,
  .ambient-triangles:not(.force-motion) .tri {
    animation: none !important;
  }
}
</style>
