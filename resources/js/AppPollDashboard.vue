<script setup>
import TheHeader from "./components/TheHeader.vue";
import { routes } from "./stores/routes";
import { useHashRoute } from "./composables/useHashRoute";

const props = defineProps({
    loginUrl: { type: String, default: null },
});

const { currentComponent, currentRoute, navigateTo } = useHashRoute(routes);
</script>

<template>
    <TheHeader title="Mes sondages" />
    <main>
        <Transition mode="out-in">
            <KeepAlive>
                <component
                    :is="currentComponent"
                    :key="currentRoute.hash"
                    :loginUrl="props.loginUrl"
                    @navigate="navigateTo"
                />
            </KeepAlive>
        </Transition>
    </main>
</template>

<style>
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    font-family: system-ui, sans-serif;
    background: #f5f5f5;
}
main {
    padding: 1.5rem;
}
.v-enter-active,
.v-leave-active {
    transition: opacity 0.15s ease;
}
.v-enter-from,
.v-leave-to {
    opacity: 0;
}
</style>
