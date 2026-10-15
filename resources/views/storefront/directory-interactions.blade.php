<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('salonDirectory', (initial) => {
        let timer, controller, revision = 0;
        return {
            q: initial.q || '',
            department: String(initial.department_id || ''),
            sort: typeof initial.sort === 'string' ? initial.sort : 'rating',
            loading: false,
            error: '',
            announcement: '',
            init() {
                this.$watch('q', () => this.queue(350));
                this.$watch('department', () => this.queue());
                this.$watch('sort', () => this.queue());
            },
            destroy() {
                clearTimeout(timer);
                controller?.abort();
            },
            queue(delay = 0) {
                clearTimeout(timer);
                controller?.abort();
                const current = ++revision;
                this.loading = true;
                this.error = '';
                timer = setTimeout(() => this.update(current), delay);
            },
            async update(current) {
                controller = new AbortController();
                const url = new URL(window.location.href);
                url.search = '';
                if (this.q.trim()) url.searchParams.set('q', this.q.trim());
                if (this.department) url.searchParams.set('department_id', this.department);
                if (this.$refs.filters.elements.sort) url.searchParams.set('sort', this.sort);
                try {
                    const response = await fetch(url, {
                        signal: controller.signal,
                        redirect: 'error',
                        headers: { 'Accept': 'text/html' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok || response.redirected) throw new Error('Unable to load salons');
                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const results = page.querySelector('#salon-results');
                    if (!results) throw new Error('Missing salon results');
                    if (current !== revision) return;
                    const target = this.$refs.results;
                    Alpine.mutateDom(() => {
                        [...target.children].forEach(child => Alpine.destroyTree(child));
                        target.replaceChildren(...results.childNodes);
                        [...target.children].forEach(child => Alpine.initTree(child));
                    });
                    history.replaceState(history.state, '', url);
                    this.announcement = this.$refs.results.querySelector('[data-result-count]').textContent.trim();
                } catch (error) {
                    if (error.name !== 'AbortError' && current === revision) {
                        this.error = 'Could not update salons. Your previous results are still shown. Please try again.';
                    }
                } finally {
                    if (current === revision) this.loading = false;
                }
            },
            reset() {
                this.q = '';
                this.department = '';
                this.sort = 'rating';
                this.queue();
            },
        };
    });
});
</script>
