{{--
    Campana de notificaciones del panel de administración — contador de no
    leídas actualizado por polling (no hay Reverb/Echo/Pusher instalado en
    el proyecto, ver App\Http\Controllers\Admin\NotificationController) y un
    dropdown con el historial reciente. Clic en un ítem marca como leída y
    navega a su link (si tiene). Self-contained, sin JS externo — mismo
    criterio que partials/theme-switch.blade.php.
--}}
<div
    x-data="{
        open: false,
        count: 0,
        items: [],
        loading: false,
        async refreshCount() {
            try {
                const res = await fetch('{{ route('admin.notifications.unread-count') }}', { headers: { Accept: 'application/json' } });
                const data = await res.json();
                this.count = data.count;
            } catch (e) {}
        },
        async loadItems() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('admin.notifications.index') }}', { headers: { Accept: 'application/json' } });
                const data = await res.json();
                this.items = data.notifications;
            } catch (e) {}
            this.loading = false;
        },
        async toggle() {
            this.open = !this.open;
            if (this.open) await this.loadItems();
        },
        async markRead(item) {
            try {
                await fetch(`/admin/notifications/${item.id}/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                });
            } catch (e) {}
            if (item.link) window.location.href = item.link;
            else { item.read_at = new Date().toISOString(); await this.refreshCount(); }
        },
        async markAllRead() {
            try {
                await fetch('{{ route('admin.notifications.mark-all-read') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                });
            } catch (e) {}
            this.items = this.items.map(i => ({ ...i, read_at: i.read_at || new Date().toISOString() }));
            this.count = 0;
        },
    }"
    x-init="refreshCount(); setInterval(() => refreshCount(), 45000)"
    @click.outside="open = false"
    class="relative"
>
    <button
        type="button"
        @click="toggle()"
        class="relative grid size-10 place-items-center rounded-full border border-paper-200 text-sky-700 hover:bg-paper-100"
        aria-label="Notificaciones"
    >
        @include('partials.admin-nav-icon', ['name' => 'bell'])
        <span
            x-show="count > 0"
            x-cloak
            x-text="count > 99 ? '99+' : count"
            class="absolute -right-1 -top-1 grid min-w-[1.15rem] place-items-center rounded-full bg-clay-500 px-1 text-[10px] font-bold leading-none text-white"
            style="height: 1.15rem;"
        ></span>
    </button>

    <div
        x-show="open"
        x-transition
        x-cloak
        class="absolute right-0 z-50 mt-2 w-80 max-w-[90vw] rounded-2xl border border-paper-200 bg-card-fixed p-2 shadow-xl"
    >
        <div class="flex items-center justify-between gap-2 px-2 py-1.5">
            <p class="text-sm font-semibold text-on-card-fixed">Notificaciones</p>
            <button type="button" @click="markAllRead()" class="text-xs font-semibold text-sky-700 hover:underline" x-show="items.length > 0">
                Marcar todas como leídas
            </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <p x-show="loading" class="px-2 py-4 text-center text-sm text-on-card-fixed-soft">Cargando…</p>
            <p x-show="!loading && items.length === 0" class="px-2 py-4 text-center text-sm text-on-card-fixed-soft">No hay notificaciones.</p>

            <template x-for="item in items" :key="item.id">
                <button
                    type="button"
                    @click="markRead(item)"
                    class="block w-full rounded-xl px-2 py-2 text-left hover:bg-paper-100"
                    :class="item.read_at ? 'opacity-60' : ''"
                >
                    <p class="text-sm font-semibold text-on-card-fixed" x-text="item.title"></p>
                    <p class="text-xs text-on-card-fixed-soft" x-text="item.body" x-show="item.body"></p>
                </button>
            </template>
        </div>
    </div>
</div>
