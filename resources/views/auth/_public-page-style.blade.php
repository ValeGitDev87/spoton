<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 24px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        background: #f6f7f9;
        color: #101828;
    }
    .panel {
        width: min(480px, 100%);
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 24px;
    }
    h1 { margin: 0 0 12px; font-size: 24px; }
    p { margin: 0 0 14px; color: #475467; line-height: 1.5; }
    label {
        display: block;
        margin: 12px 0 5px;
        font-size: 13px;
        font-weight: 750;
        color: #344054;
    }
    input {
        width: 100%;
        height: 42px;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        padding: 8px 10px;
        font: inherit;
    }
    button, .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        border: 0;
        border-radius: 8px;
        padding: 0 14px;
        background: #111827;
        color: #fff;
        font: inherit;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
    }
    .errors {
        margin-bottom: 14px;
        padding: 10px 12px;
        border-radius: 8px;
        background: #fef3f2;
        color: #b42318;
    }
    .success {
        margin-bottom: 14px;
        padding: 10px 12px;
        border-radius: 8px;
        background: #ecfdf3;
        color: #027a48;
    }
</style>
