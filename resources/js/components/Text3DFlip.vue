<template>
  <span class="text-3d-flip-wrapper" :class="containerClass">
    <!-- Real text for screen readers / SEO, same accessibility fix as the React version -->
    <span class="sr-only">{{ text }}</span>

    <span
      class="text-3d-flip"
      :class="`dir-${rotateDirection}`"
      :style="{ perspective: '800px' }"
      aria-hidden="true"
      :key="animKey"
    >
      <span
        v-for="(word, wordIdx) in words"
        :key="wordIdx"
        class="flip-word"
      >
        <span
          v-for="(char, charIdx) in word.chars"
          :key="charIdx"
          class="flip-char"
        >
          <span
            class="flip-char-visible"
            :class="[textClass, { animate: true }]"
            :style="{ animationDelay: `${char.delay}s`, animationDuration: `${animDuration}s` }"
          >{{ char.value }}</span>
        </span>
        <span v-if="wordIdx < words.length - 1" :class="textClass">&nbsp;</span>
      </span>
    </span>
  </span>
</template>

<script>
export default {
  name: 'Text3DFlip',

  props: {
    text: { type: String, required: true },
    rotateDirection: {
      type: String,
      default: 'top',
      validator: (v) => ['top', 'bottom', 'left', 'right'].includes(v),
    },
    staggerDuration: { type: Number, default: 0.03 },
    staggerFrom: {
      type: String,
      default: 'first',
      validator: (v) => ['first', 'last', 'center'].includes(v),
    },
    animDuration: { type: Number, default: 0.7 },
    textClass: { type: String, default: '' },
    containerClass: { type: String, default: '' },
  },

  data() {
    return {
      animKey: 0, // bump this to force-restart the animation, e.g. via a "replay" button or watcher
    };
  },

  computed: {
    words() {
      const rawWords = this.text.split(' ');
      const totalChars = rawWords.reduce((sum, w) => sum + w.length, 0);
      let flatIndex = 0;

      return rawWords.map((word) => ({
        chars: word.split('').map((char) => {
          const idx = flatIndex++;
          const delayIndex = this.staggerFrom === 'last'
            ? totalChars - 1 - idx
            : this.staggerFrom === 'center'
              ? Math.abs(idx - totalChars / 2)
              : idx;

          return { value: char, delay: delayIndex * this.staggerDuration };
        }),
      }));
    },
  },

  methods: {
    replay() {
      this.animKey++; // changing :key re-mounts the element, restarting all CSS animations
    },
  },
};
</script>

<style scoped>
.text-3d-flip-wrapper {
  position: relative;
  display: inline-block;
}

.text-3d-flip {
  display: inline-flex;
  flex-wrap: wrap;
}

.flip-word {
  display: inline-flex;
  white-space: nowrap;
}

.flip-char {
  position: relative;
  display: inline-block;
  transform-style: preserve-3d;
}

.flip-char-visible {
  display: inline-block;
  backface-visibility: hidden;
  animation-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);
  animation-fill-mode: both;
}

.dir-top .flip-char-visible { transform-origin: 50% 0%; animation-name: flip-in-top; }
.dir-bottom .flip-char-visible { transform-origin: 50% 100%; animation-name: flip-in-bottom; }
.dir-left .flip-char-visible { transform-origin: 0% 50%; animation-name: flip-in-left; }
.dir-right .flip-char-visible { transform-origin: 100% 50%; animation-name: flip-in-right; }

@keyframes flip-in-top {
  from { transform: rotateX(-90deg); opacity: 0.6; }
  to   { transform: rotateX(0deg);   opacity: 1; }
}
@keyframes flip-in-bottom {
  from { transform: rotateX(90deg);  opacity: 0.6; }
  to   { transform: rotateX(0deg);   opacity: 1; }
}
@keyframes flip-in-left {
  from { transform: rotateY(-90deg); opacity: 0.6; }
  to   { transform: rotateY(0deg);   opacity: 1; }
}
@keyframes flip-in-right {
  from { transform: rotateY(90deg);  opacity: 0.6; }
  to   { transform: rotateY(0deg);   opacity: 1; }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
