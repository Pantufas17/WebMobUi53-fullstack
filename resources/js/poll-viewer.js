/*
entrypoint de l app Vue pour la page de vote.
cest le fichier que vite va compiler en premier, il cree l app Vue et la monte 
dans la page Blade. cest un peu la meme vibe que poll-dashboard.js fait par le prof

et comme on a besoin dun entrypoint et comme on la deuxieme app
appPollViewer separée du dahsboard, il lui faut son propre fichier
dentrypoint, sans ca Vite va pas savoir quoi compiler pour la page de vote.
*/

import "./bootstrap";
import { createApp } from "vue";
import App from "./AppPollViewer.vue";

const el = document.getElementById("app");
const props = JSON.parse(el.dataset.props ?? "{}");

createApp(App, props).mount(el);
