import { ref, readonly } from "vue";

const _currentPoll = ref(null);

export const currentPoll = readonly(_currentPoll);

export function setCurrentPoll(poll) {
    _currentPoll.value = poll;
}
