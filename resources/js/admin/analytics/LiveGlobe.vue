<template>
    <div ref="container" class="live-globe-container">
        <div v-if="loading" class="live-globe-loading">Loading map…</div>
        <div v-else-if="loadError" class="live-globe-error">
            Land map data failed to load.
            <br />
            <button class="retry-btn" @click="retryLandData">Retry</button>
        </div>
    </div>
</template>

<script>
import Globe from 'globe.gl';
import * as THREE from 'three';

// Precomputed once (offline) and hosted as a static file — see the
// generation script if the dot pattern ever needs regenerating (e.g.
// different grid density). This is just [[lat, lng], ...] pairs for
// every land point, so there's zero point-in-polygon computation left
// to do in the browser — just fetch and render.
const LAND_DOTS_URL = '/geo/land_dots.json';
const DOT_COLOR = '#20c9a6';
const DOT_RADIUS = 0.14;
const DOT_ALTITUDE = 0.004;

export default {
    name: 'LiveGlobe',

    props: {
        // [{ latitude, longitude, city, current_path, last_seen_at }, ...]
        visitors: { type: Array, default: () => [] },
    },

    data() {
        return {
            loading: true,
            loadError: false,
            globeInstance: null,
            landDots: [],
            _destroyed: false,
        };
    },

    computed: {
        visitorPoints() {
            return this.visitors.map((v) => ({
                lat: v.latitude,
                lng: v.longitude,
                type: 'visitor',
                city: v.city || 'Unknown',
                label: `Page view · ${this.formatTime(v.last_seen_at)}`,
            }));
        },
    },

    watch: {
        // Re-render just the points layer when live visitors change,
        // without rebuilding the whole globe/land-dot grid.
        visitorPoints() {
            this.refreshPoints();
        },
    },

    mounted() {
        // Defer to nextTick — guarantees the container ref is actually
        // attached to the live document, not just present in Vue's virtual
        // DOM. Matters if this component sits inside a Vuetify tab/dialog
        // that hasn't finished its own mount/transition yet.
        this.$nextTick(() => {
            if (!this._destroyed && this.$refs.container) {
                this.initGlobe();
            }
        });
    },

    beforeUnmount() {
        // Set this FIRST — any in-flight fetch() callback (loadLandData)
        // checks this before touching the DOM, preventing the exact race
        // that causes "insertBefore, parent is null": the fetch resolving
        // after the container's already been torn down.
        this._destroyed = true;

        window.removeEventListener('resize', this._resizeHandler);

        // globe.gl doesn't expose an official teardown method; clearing the
        // container's innerHTML releases the WebGL context and DOM nodes it
        // created, avoiding a leak if this component mounts/unmounts repeatedly.
        if (this.$refs.container) {
            this.$refs.container.innerHTML = '';
        }

        this.globeInstance = null;
    },

    methods: {
        initGlobe() {
            const el = this.$refs.container;

            this.globeInstance = Globe()(el)
                .globeImageUrl(null)
                .globeMaterial(new THREE.MeshPhongMaterial({ color: '#eef5f4', transparent: true, opacity: 0.9 }))
                .backgroundColor('rgba(0,0,0,0)')
                .showAtmosphere(true)
                .atmosphereColor('#a8e6dd')
                .atmosphereAltitude(0.2)
                .pointsData([])
                .pointLat('lat')
                .pointLng('lng')
                .pointColor((d) => (d.type === 'visitor' ? '#7C4DFF' : DOT_COLOR))
                .pointRadius((d) => (d.type === 'visitor' ? 0.55 : DOT_RADIUS))
                .pointAltitude((d) => (d.type === 'visitor' ? 0.025 : DOT_ALTITUDE))
                .pointResolution(6)
                .pointLabel((d) => (d.type === 'visitor'
                    ? `<div style="background:#fff;color:#111;padding:6px 10px;border-radius:6px;font-family:sans-serif;font-size:12px;box-shadow:0 2px 8px rgba(0,0,0,0.15)"><b>${d.city}</b><br/>${d.label}</div>`
                    : ''))
                .width(el.clientWidth)
                .height(el.clientHeight);

            this.globeInstance.pointOfView({ lat: 30, lng: 10, altitude: 1.7 }, 0);
            this.globeInstance.controls().autoRotate = true;
            this.globeInstance.controls().autoRotateSpeed = 0.4;

            this.loadLandData();

            this._resizeHandler = () => {
                this.globeInstance.width(el.clientWidth).height(el.clientHeight);
            };
            window.addEventListener('resize', this._resizeHandler);
        },

        loadLandData() {
            this.loading = true;
            this.loadError = false;

            fetch(LAND_DOTS_URL)
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    return res.json();
                })
                .then((pairs) => {
                    if (this._destroyed) return;

                    // pairs is [[lat, lng], ...] — expand to the {lat, lng, type}
                    // shape pointsData expects.
                    this.landDots = pairs.map(([lat, lng]) => ({ lat, lng, type: 'land' }));
                    this.refreshPoints();
                    this.loading = false;
                })
                .catch((err) => {
                    if (this._destroyed) return;

                    console.warn('Could not load land dot data:', err);
                    this.refreshPoints();
                    this.loading = false;
                    this.loadError = true;
                });
        },

        retryLandData() {
            this.loadLandData();
        },

        refreshPoints() {
            if (!this.globeInstance || this._destroyed) return;
            this.globeInstance.pointsData([...this.landDots, ...this.visitorPoints]);
        },

        formatTime(value) {
            if (!value) return '';
            return new Date(value).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        },
    },
};
</script>

<style scoped>
.live-globe-container {
    position: relative;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at 30% 40%, #e8f5f4 0%, #f5f6f8 60%);
    border-radius: 12px;
}

.live-globe-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 13px;
    color: #888;
}

.live-globe-error {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 13px;
    color: #888;
    text-align: center;
    max-width: 240px;
}

.retry-btn {
    margin-top: 10px;
    font-size: 12px;
    padding: 6px 14px;
    border-radius: 6px;
    border: 1px solid #ddd;
    background: #f5f5f5;
    color: #333;
    cursor: pointer;
}
.retry-btn:hover { background: #ececec; }
</style>
