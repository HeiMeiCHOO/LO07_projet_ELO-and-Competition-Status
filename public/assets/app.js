// 提交关键表单前给出二次确认提示。
document.addEventListener('DOMContentLoaded', () => {
    // 只处理带 data-confirm 的表单。
    const forms = document.querySelectorAll('form[data-confirm]');
    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            // 若用户取消，则阻止提交。
            const message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});
