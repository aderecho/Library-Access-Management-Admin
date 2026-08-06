const dashboardTabs = document.querySelector('[data-dashboard-tabs]');

if (dashboardTabs) {
    const tabList = dashboardTabs.querySelector('[role="tablist"]');
    const tabs = [...dashboardTabs.querySelectorAll('[role="tab"]')];
    const panels = [...document.querySelectorAll('[data-dashboard-panel]')];
    const tabNames = tabs.map((tab) => tab.dataset.dashboardTab);
    const monthlyChartScroll = document.querySelector('[data-monthly-chart-scroll]');
    let hasPositionedMobileChart = false;

    const positionMobileChart = () => {
        if (!monthlyChartScroll || hasPositionedMobileChart || !window.matchMedia('(max-width: 650px)').matches) {
            return;
        }

        const chart = monthlyChartScroll.querySelector('.line-chart');
        const monthIndex = Number(monthlyChartScroll.dataset.currentMonthIndex || 0);

        if (!chart || chart.scrollWidth <= monthlyChartScroll.clientWidth) {
            return;
        }

        const monthPosition = (Math.max(0, Math.min(11, monthIndex)) / 11) * chart.scrollWidth;
        monthlyChartScroll.scrollLeft = Math.max(0, monthPosition - (monthlyChartScroll.clientWidth / 2));
        hasPositionedMobileChart = true;
    };

    const activateTab = (name, { focus = false, updateHash = true } = {}) => {
        const activeTab = tabs.find((tab) => tab.dataset.dashboardTab === name);

        if (!activeTab) {
            return;
        }

        tabs.forEach((tab) => {
            const isActive = tab === activeTab;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
        });

        let activePanel = null;

        panels.forEach((panel) => {
            const isActive = panel.dataset.dashboardPanel === name;
            panel.hidden = !isActive;
            panel.classList.toggle('is-active', isActive);

            if (isActive) {
                activePanel = panel;
            }
        });

        tabList.scrollTo({
            left: activeTab.offsetLeft - ((tabList.clientWidth - activeTab.offsetWidth) / 2),
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        });

        if (focus) {
            activeTab.focus();
        }

        if (updateHash) {
            const nextUrl = new URL(window.location.href);
            nextUrl.hash = name === 'overview' ? '' : name;
            window.history.replaceState({}, '', nextUrl);
        }

        if (activePanel && window.matchMedia('(min-width: 951px)').matches && (updateHash || name !== 'overview')) {
            window.requestAnimationFrame(() => {
                activePanel.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                    block: 'start',
                });
            });
        } else if (name === 'entry-analytics') {
            window.requestAnimationFrame(positionMobileChart);
        }
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab.dataset.dashboardTab));
        tab.addEventListener('keydown', (event) => {
            let nextIndex = index;

            if (event.key === 'ArrowRight') {
                nextIndex = (index + 1) % tabs.length;
            } else if (event.key === 'ArrowLeft') {
                nextIndex = (index - 1 + tabs.length) % tabs.length;
            } else if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = tabs.length - 1;
            } else {
                return;
            }

            event.preventDefault();
            activateTab(tabs[nextIndex].dataset.dashboardTab, { focus: true });
        });
    });

    const activateFromHash = () => {
        const requestedTab = window.location.hash.slice(1);
        activateTab(tabNames.includes(requestedTab) ? requestedTab : 'overview', { updateHash: false });
    };

    window.addEventListener('hashchange', activateFromHash);
    activateFromHash();
}
