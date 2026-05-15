import DashboardPage from "../pages/DashboardPage.vue";
import PollEditorPage from "../pages/PollEditorPage.vue";

export const routes = [
    { hash: "#dashboard", label: "Mes sondages", component: DashboardPage },
    { hash: "#create", label: "Nouveau sondage", component: PollEditorPage },
    { hash: "#edit", label: "Modifier", component: PollEditorPage },
];
