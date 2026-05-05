import './bootstrap';

// Alpine ships with Livewire 4 — importing it separately causes
// "Detected multiple instances of Alpine running" warnings and breaks reactivity
// (badges blink, x-show flickers). Letting Livewire own Alpine is the supported path.
