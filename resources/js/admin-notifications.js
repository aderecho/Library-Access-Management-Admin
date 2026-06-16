import $ from 'jquery';
import initializeNotify from 'notifyjs-browser';

window.$ = window.jQuery = $;
initializeNotify(window, $);

$.notify.addStyle('up-cebu', {
    html: `
        <div>
            <div class="up-toast-icon" data-notify-html="icon"></div>
            <div class="up-toast-content">
                <strong data-notify-text="title"></strong>
                <span data-notify-text="message"></span>
            </div>
            <button class="up-toast-close" type="button" aria-label="Dismiss notification">×</button>
        </div>
    `,
    classes: {
        base: {
            'align-items': 'center',
            'backdrop-filter': 'blur(14px)',
            'background-color': '#ffffff',
            'border': '1px solid rgba(104, 0, 11, 0.12)',
            'border-left': '5px solid #68000b',
            'border-radius': '14px',
            'box-shadow': '0 18px 45px rgba(52, 20, 23, 0.20)',
            'box-sizing': 'border-box',
            'color': '#21352f',
            'display': 'flex',
            'gap': '12px',
            'max-width': '390px',
            'min-width': '300px',
            'padding': '14px 16px',
            'position': 'relative',
            'white-space': 'normal',
        },
        success: {
            'border-left-color': '#267039',
        },
        error: {
            'border-left-color': '#ad3027',
        },
        warn: {
            'border-left-color': '#d99b22',
        },
        info: {
            'border-left-color': '#0f4738',
        },
    },
});

const notificationDetails = {
    success: { icon: '✓', title: 'Success' },
    error: { icon: '!', title: 'Something went wrong' },
    warn: { icon: '!', title: 'Attention' },
    info: { icon: 'i', title: 'Information' },
};

const notifications = document.querySelectorAll('[data-notification]');

notifications.forEach((notification) => {
    const type = notification.dataset.type ?? 'info';
    const details = notificationDetails[type] ?? notificationDetails.info;
    $.notify({
        icon: details.icon,
        title: details.title,
        message: notification.dataset.message,
    }, {
        autoHideDelay: Number(notification.dataset.duration ?? 5000),
        className: type,
        globalPosition: 'top right',
        showAnimation: 'fadeIn',
        showDuration: 250,
        hideAnimation: 'fadeOut',
        hideDuration: 200,
        style: 'up-cebu',
    });
});

$(document).on('click', '.up-toast-close', function () {
    $(this).closest('.notifyjs-wrapper').trigger('notify-hide');
});
