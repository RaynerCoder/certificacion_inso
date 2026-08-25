<style>
    :root {
        --cert-green-950: #03451f;
        --cert-green-900: #075d2b;
        --cert-green-800: #087a37;
        --cert-green-700: #0a8f43;
        --cert-green-100: #e8f4ec;
        --cert-gold: #eda719;
        --cert-slate-900: #17211b;
        --cert-slate-600: #5e6962;
        --cert-border: #c8d8ce;
        --cert-error: #b42318;
    }

    .cert-login-page {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        overflow-x: hidden;
        background: #f7faf8;
        color: var(--cert-slate-900);
    }

    .cert-login-main {
        min-height: calc(100vh - 58px);
        flex: 1 0 auto;
        display: grid;
        grid-template-columns: minmax(500px, .93fr) minmax(590px, 1.07fr);
    }

    .cert-login-brand {
        position: relative;
        z-index: 1;
        min-height: 100%;
        overflow: hidden;
        display: grid;
        place-items: center;
        padding: 44px 11% 42px 7%;
        background:
            radial-gradient(circle at 34% 30%, rgba(20, 151, 76, .45), transparent 38%),
            linear-gradient(145deg, var(--cert-green-900), var(--cert-green-950));
        clip-path: polygon(0 0, 87% 0, 100% 50%, 87% 100%, 0 100%);
        color: #fff;
    }

    .cert-brand-content {
        position: relative;
        z-index: 2;
        width: min(100%, 620px);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .cert-brand-logo-crop {
        width: min(30vw, 410px);
        height: min(23vw, 315px);
        overflow: hidden;
        flex: 0 0 auto;
    }

    .cert-brand-logo-crop img {
        display: block;
        width: 100%;
        height: auto;
        filter: drop-shadow(0 12px 20px rgba(0, 35, 16, .22));
    }

    .cert-brand-copy {
        width: 100%;
    }

    .cert-brand-institution {
        margin: 8px 0 0;
        font-size: clamp(1.35rem, 2.05vw, 2.15rem);
        line-height: 1.18;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .cert-brand-divider {
        display: block;
        width: min(78%, 440px);
        height: 4px;
        margin: 22px auto 18px;
        border-radius: 999px;
        background: linear-gradient(90deg, transparent, var(--cert-gold) 12%, var(--cert-gold) 88%, transparent);
    }

    .cert-brand-description {
        margin: 0;
        font-size: clamp(.82rem, 1.15vw, 1.1rem);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: .025em;
    }

    .cert-brand-system-name {
        margin: 6px 0 0;
        font-size: clamp(2.9rem, 4.35vw, 4.9rem);
        line-height: 1;
        font-weight: 800;
        letter-spacing: .015em;
    }

    .cert-brand-pattern {
        position: absolute;
        width: 120px;
        fill: none;
        stroke: rgba(255, 255, 255, .10);
        stroke-width: 4;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .cert-brand-pattern--one {
        top: 2%;
        left: 3%;
        transform: rotate(-4deg);
    }

    .cert-brand-pattern--two {
        top: 8%;
        right: 13%;
        transform: rotate(14deg) scale(.9);
    }

    .cert-brand-pattern--three {
        right: 12%;
        bottom: 3%;
        transform: rotate(-10deg) scale(1.05);
    }

    .cert-login-access {
        min-width: 0;
        display: grid;
        place-items: center;
        padding: 42px 7% 42px 3%;
        background:
            radial-gradient(circle at 82% 10%, rgba(8, 122, 55, .05), transparent 28%),
            #f8faf9;
    }

    .cert-login-card {
        width: min(100%, 610px);
        padding: 30px 40px 34px;
        border: 1px solid #dbe5de;
        border-radius: 18px;
        background: rgba(255, 255, 255, .97);
        box-shadow: 0 18px 45px rgba(20, 58, 36, .12), 0 3px 10px rgba(20, 58, 36, .06);
    }

    .cert-login-icon {
        display: block;
        width: 82px;
        height: 82px;
        margin: 0 auto 4px;
        object-fit: contain;
    }

    .cert-login-header {
        text-align: center;
    }

    .cert-login-header h2 {
        margin: 0;
        color: var(--cert-green-900);
        font-size: clamp(1.65rem, 2.1vw, 2.15rem);
        line-height: 1.2;
        font-weight: 800;
    }

    .cert-login-header p {
        margin: 5px 0 0;
        color: var(--cert-slate-600);
        font-size: .98rem;
    }

    .cert-login-status {
        margin-top: 18px;
        padding: 11px 14px;
        border: 1px solid #b8ddc5;
        border-radius: 10px;
        background: #eef8f1;
        color: var(--cert-green-900);
        font-size: .9rem;
    }

    .cert-login-form {
        display: grid;
        gap: 17px;
        margin-top: 25px;
    }

    .cert-form-field label,
    .cert-captcha-box legend {
        display: block;
        margin-bottom: 7px;
        color: var(--cert-green-900);
        font-size: .9rem;
        font-weight: 700;
    }

    .cert-input-wrap {
        min-height: 52px;
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 0 14px;
        border: 1px solid var(--cert-border);
        border-radius: 9px;
        background: #fff;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .cert-input-wrap:focus-within,
    .cert-captcha-input:focus {
        border-color: var(--cert-green-700);
        box-shadow: 0 0 0 3px rgba(10, 143, 67, .13);
        outline: none;
    }

    .cert-input-wrap--error {
        border-color: #e09b95;
    }

    .cert-input-wrap > svg,
    .cert-input-action svg {
        width: 22px;
        height: 22px;
        flex: 0 0 auto;
        fill: none;
        stroke: #6f7a73;
        stroke-width: 1.7;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .cert-login-form .cert-input-wrap input {
        min-width: 0;
        flex: 1 1 auto;
        padding: 13px 0;
        border: 0 !important;
        outline: 0 !important;
        background: transparent !important;
        color: var(--cert-slate-900);
        font-size: .96rem;
        box-shadow: none !important;
        appearance: none;
        --tw-ring-color: transparent !important;
        --tw-ring-offset-shadow: 0 0 #0000 !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    /* El borde de foco pertenece al contenedor. Así evitamos un segundo marco dentro del campo. */
    .cert-login-form .cert-input-wrap input:focus,
    .cert-login-form .cert-input-wrap input:focus-visible,
    .cert-login-form .cert-input-wrap input:active {
        border: 0 !important;
        outline: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        --tw-ring-color: transparent !important;
        --tw-ring-offset-shadow: 0 0 #0000 !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    /* Chrome puede pintar de azul los datos guardados; mantenemos el fondo blanco del diseño. */
    .cert-login-form .cert-input-wrap input:-webkit-autofill,
    .cert-login-form .cert-input-wrap input:-webkit-autofill:hover,
    .cert-login-form .cert-input-wrap input:-webkit-autofill:focus {
        -webkit-text-fill-color: var(--cert-slate-900);
        -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
        caret-color: var(--cert-slate-900);
        border: 0 !important;
        outline: 0 !important;
    }

    .cert-input-wrap input::placeholder,
    .cert-captcha-input::placeholder {
        color: #9aa39d;
    }

    .cert-input-action {
        display: grid;
        width: 36px;
        height: 36px;
        flex: 0 0 auto;
        place-items: center;
        border: 0;
        border-radius: 7px;
        background: transparent;
        cursor: pointer;
    }

    .cert-input-action:hover {
        background: #f0f5f2;
    }

    .cert-login-options {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-top: -2px;
        font-size: .88rem;
    }

    .cert-remember-option {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--cert-slate-600);
        cursor: pointer;
    }

    .cert-remember-option input {
        width: 18px;
        height: 18px;
        border: 1px solid #9aa79f;
        border-radius: 4px;
        color: var(--cert-green-800);
        accent-color: var(--cert-green-800);
    }

    .cert-remember-option input:focus {
        outline: 2px solid rgba(8, 122, 55, .24);
        outline-offset: 2px;
        box-shadow: none;
        --tw-ring-color: transparent;
        --tw-ring-offset-shadow: 0 0 #0000;
        --tw-ring-shadow: 0 0 #0000;
    }

    .cert-login-options a {
        color: var(--cert-green-800);
        font-weight: 600;
        text-decoration: none;
    }

    .cert-login-options a:hover {
        text-decoration: underline;
    }

    .cert-captcha-box {
        min-width: 0;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
    }

    .cert-captcha-box--error {
        background: transparent;
    }

    .cert-captcha-box--error .cert-captcha-input {
        border-color: #e09b95;
    }

    .cert-captcha-box legend {
        width: 100%;
        padding: 0;
        margin: 0 0 7px;
    }

    .cert-captcha-row {
        display: flex;
        gap: 0;
        align-items: center;
        justify-content: center;
    }

    .cert-captcha-row img {
        width: 180px;
        max-width: calc(100% - 36px);
        height: 36px;
        flex: 0 1 180px;
        aspect-ratio: 5 / 1;
        display: block;
        object-fit: fill;
        border: 1px solid #c5d6cb;
        border-radius: 6px 0 0 6px;
        background: #fff;
    }

    .cert-captcha-refresh {
        display: grid;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        place-items: center;
        padding: 0;
        border: 1px solid #c5d6cb;
        border-left: 0;
        border-radius: 0 6px 6px 0;
        background: #fff;
        color: var(--cert-green-800);
        cursor: pointer;
        box-shadow: none;
        transition: color .2s ease, transform .2s ease;
    }

    .cert-captcha-refresh:hover {
        background: var(--cert-green-100);
        color: var(--cert-green-950);
        transform: none;
    }

    .cert-captcha-refresh:active {
        transform: scale(.94);
    }

    .cert-captcha-refresh svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .cert-captcha-input {
        width: 100%;
        height: 46px;
        margin-top: 8px;
        padding: 0 13px;
        border: 1px solid #c5d6cb;
        border-radius: 8px;
        background: #fff;
        color: var(--cert-slate-900);
        font-size: .94rem;
        text-transform: uppercase;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .cert-login-form .cert-captcha-input:focus,
    .cert-login-form .cert-captcha-input:focus-visible {
        border-color: var(--cert-green-700) !important;
        outline: 0 !important;
        background: #fff !important;
        box-shadow: 0 0 0 3px rgba(10, 143, 67, .13) !important;
        --tw-ring-color: transparent !important;
        --tw-ring-offset-shadow: 0 0 #0000 !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    .cert-login-form .cert-captcha-input:-webkit-autofill,
    .cert-login-form .cert-captcha-input:-webkit-autofill:focus {
        -webkit-text-fill-color: var(--cert-slate-900);
        -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
        caret-color: var(--cert-slate-900);
    }

    .cert-field-error {
        margin: 6px 0 0;
        color: var(--cert-error);
        font-size: .8rem;
        line-height: 1.35;
    }

    .cert-login-submit {
        min-height: 52px;
        border: 0;
        border-radius: 8px;
        background: linear-gradient(90deg, var(--cert-green-800), var(--cert-green-700), var(--cert-green-800));
        color: #fff;
        font-size: .98rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .025em;
        cursor: pointer;
        box-shadow: 0 7px 15px rgba(8, 122, 55, .16);
        transition: filter .2s ease, transform .2s ease;
    }

    .cert-login-submit:hover {
        filter: brightness(.94);
    }

    .cert-login-submit:active {
        transform: translateY(1px);
    }

    .cert-input-action:focus-visible,
    .cert-captcha-refresh:focus-visible,
    .cert-login-submit:focus-visible,
    .cert-login-options a:focus-visible {
        outline: 3px solid rgba(237, 167, 25, .48);
        outline-offset: 2px;
    }

    .cert-login-footer {
        position: relative;
        z-index: 3;
        min-height: 58px;
        flex: 0 0 58px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        border-top: 1px solid #dbe8df;
        background: #f0f8f3;
        color: var(--cert-green-900);
        font-size: .93rem;
        font-weight: 500;
    }

    .cert-login-footer svg {
        width: 25px;
        height: 25px;
        fill: var(--cert-green-800);
        stroke: #fff;
        stroke-width: 1.6;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    @media (max-width: 1180px) {
        .cert-login-main {
            grid-template-columns: minmax(430px, .88fr) minmax(540px, 1.12fr);
        }

        .cert-login-brand {
            padding-left: 6%;
        }

        .cert-login-card {
            padding-inline: 32px;
        }
    }

    @media (max-width: 960px) {
        .cert-login-main {
            display: block;
            min-height: auto;
        }

        .cert-login-brand {
            min-height: 320px;
            padding: 24px 8% 48px;
            clip-path: polygon(0 0, 100% 0, 100% 86%, 50% 100%, 0 86%);
        }

        .cert-brand-content {
            max-width: 760px;
            display: grid;
            grid-template-columns: 210px minmax(0, 1fr);
            gap: 24px;
        }

        .cert-brand-logo-crop {
            width: 210px;
            height: 165px;
        }

        .cert-brand-institution {
            margin-top: 0;
            font-size: 1.48rem;
        }

        .cert-brand-divider {
            margin-block: 14px 12px;
        }

        .cert-brand-description {
            font-size: .82rem;
        }

        .cert-brand-system-name {
            font-size: 3.15rem;
        }

        .cert-login-access {
            min-height: auto;
            padding: 38px 22px 48px;
        }

        .cert-login-card {
            width: min(100%, 620px);
        }
    }

    @media (max-width: 640px) {
        .cert-login-brand {
            min-height: 285px;
            padding: 18px 18px 44px;
        }

        .cert-brand-content {
            display: flex;
            gap: 0;
        }

        .cert-brand-logo-crop {
            width: 155px;
            height: 120px;
        }

        .cert-brand-institution {
            margin-top: 2px;
            font-size: 1.08rem;
        }

        .cert-brand-divider {
            height: 3px;
            margin-block: 11px 9px;
        }

        .cert-brand-description {
            font-size: .67rem;
        }

        .cert-brand-system-name {
            margin-top: 4px;
            font-size: clamp(2.2rem, 11vw, 3rem);
        }

        .cert-brand-pattern {
            width: 90px;
        }

        .cert-login-access {
            padding: 24px 12px 34px;
        }

        .cert-login-card {
            padding: 24px 18px 26px;
            border-radius: 14px;
        }

        .cert-login-icon {
            width: 70px;
            height: 70px;
        }

        .cert-login-form {
            gap: 15px;
            margin-top: 22px;
        }

        .cert-login-options {
            align-items: flex-start;
            font-size: .82rem;
        }

        .cert-captcha-box {
            padding-inline: 0;
        }

        .cert-captcha-row {
            gap: 0;
        }

        .cert-captcha-refresh {
            width: 36px;
            height: 36px;
            flex-basis: 36px;
        }

        .cert-captcha-row img {
            max-width: calc(100% - 36px);
        }

        .cert-login-footer {
            min-height: 56px;
            flex-basis: auto;
            padding: 10px 15px;
            text-align: center;
            font-size: .82rem;
        }
    }

    /* En pantallas de escritorio bajas reducimos espacios, pero nunca recortamos el formulario. */
    @media (min-width: 961px) and (max-height: 760px) {
        .cert-login-brand {
            padding-top: 24px;
            padding-bottom: 24px;
        }

        .cert-brand-logo-crop {
            width: min(24vw, 320px);
            height: min(18.5vw, 245px);
        }

        .cert-brand-institution {
            margin-top: 4px;
            font-size: clamp(1.2rem, 1.7vw, 1.75rem);
        }

        .cert-brand-divider {
            margin-block: 14px 12px;
        }

        .cert-brand-system-name {
            font-size: clamp(2.7rem, 3.8vw, 4rem);
        }

        .cert-login-access {
            padding-top: 24px;
            padding-bottom: 24px;
        }

        .cert-login-card {
            padding: 20px 34px 24px;
        }

        .cert-login-icon {
            width: 64px;
            height: 64px;
        }

        .cert-login-form {
            gap: 12px;
            margin-top: 18px;
        }

        .cert-input-wrap {
            min-height: 48px;
        }

        .cert-captcha-box {
            padding: 0;
        }

        .cert-captcha-input {
            height: 43px;
            margin-top: 8px;
        }

        .cert-login-submit {
            min-height: 48px;
        }
    }

    @media (max-width: 420px) {
        .cert-login-card {
            padding-inline: 14px;
        }

        .cert-login-options {
            flex-wrap: wrap;
            gap: 10px;
        }

        .cert-login-options a {
            width: 100%;
            text-align: right;
        }

        .cert-captcha-box {
            padding-inline: 0;
        }
    }
</style>
