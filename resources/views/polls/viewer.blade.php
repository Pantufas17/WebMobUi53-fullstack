<x-vue-app-layout>
    <x-slot:title>
        Sondage
        </x-slot>

        <x-slot:scripts>
            @vite(['resources/js/poll-viewer.js'])
            </x-slot>

            <div id="app" data-props='@json([
                "token" => $token,
                "loginUrl" => route("login"),
                "userId" => auth()->id(),
            ])'></div>
</x-vue-app-layout>