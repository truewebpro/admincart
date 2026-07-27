<template>
    <v-card elevation="2">
        <v-card-title class="text-subtitle-1 d-flex align-center">
            <v-icon icon="mdi-circle" color="success" size="10" class="mr-2" />
            Live Now
        </v-card-title>
        <v-card-text>
            <div class="text-h3 font-weight-bold">{{ liveCount }}</div>
            <div class="text-body-2 text-medium-emphasis mb-3">visitors active right now</div>

            <v-divider class="mb-3" />

            <div class="text-caption text-medium-emphasis mb-2">Currently viewing</div>
            <v-list density="compact" lines="one">
                <v-list-item v-for="page in currentPages" :key="page.current_path">
                    <template #prepend>
                        <v-avatar size="24" color="primary" variant="tonal">
                            {{ page.visitor_count }}
                        </v-avatar>
                    </template>
                    <v-list-item-title class="text-body-2">{{ page.current_path || 'Unknown page' }}</v-list-item-title>
                </v-list-item>
                <v-list-item v-if="!currentPages.length">
                    <v-list-item-title class="text-body-2 text-medium-emphasis">No one online right now</v-list-item-title>
                </v-list-item>
            </v-list>
        </v-card-text>
    </v-card>
</template>

<script>
const POLL_INTERVAL_MS = 30000; // refresh every 30s — frequent enough to feel "live" without hammering the endpoint

export default {
    name: 'LiveNowWidget',

    data() {
        return {
            liveCount: 0,
            currentPages: [],
            pollTimer: null,
        };
    },

    mounted() {
        this.fetchLiveNow();
        this.pollTimer = setInterval(this.fetchLiveNow, POLL_INTERVAL_MS);
    },

    beforeUnmount() {
        clearInterval(this.pollTimer);
    },

    methods: {
        async fetchLiveNow() {
            try {
                const { data } = await axios.get('/sadmin/analytics/live-now');
                this.liveCount = data.live_count;
                this.currentPages = data.current_pages || [];
            } catch (e) {
                console.error('Failed to load live visitors', e);
            }
        },
    },
};
</script>
