<x-admin-layout
    title="Cambiar contraseña | Sistema Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'Cambiar contraseña'],
    ]">

    <style>
        .perfil-password-page {
            box-sizing: border-box;
            display: block;
            margin: 0 auto !important;
            max-width: 960px !important;
            padding: 16px 0 22px;
            width: 100% !important;
        }

        .perfil-password-heading {
            align-items: flex-start;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            display: flex;
            gap: 12px;
            margin-bottom: 14px;
            padding: 12px 15px;
        }

        .perfil-password-heading-icon {
            align-items: center;
            background: #059669;
            border-radius: 9px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 auto;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .perfil-password-heading h1 {
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
            margin: 0;
        }

        .perfil-password-heading p {
            color: #64748b;
            font-size: 13px;
            line-height: 1.45;
            margin: 3px 0 0;
        }

        .perfil-password-input:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, .15) !important;
            outline: none !important;
        }

        .perfil-password-input {
            min-height: 40px;
        }

        .perfil-password-form form>div:first-child {
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
            padding: 18px;
        }

        .perfil-password-form form>div:first-child>.grid {
            column-gap: 16px;
            row-gap: 15px;
        }

        .perfil-password-form form>div:last-child {
            border: 1px solid #e2e8f0;
            border-top: 0;
        }

        @media (max-width: 640px) {
            .perfil-password-page {
                padding: 12px 0 20px;
            }

            .perfil-password-heading {
                padding: 13px;
            }

            .perfil-password-heading-icon {
                height: 34px;
                width: 34px;
            }

            .perfil-password-heading h1 {
                font-size: 16px;
            }

            .perfil-password-form form>div:first-child {
                border-radius: 10px 10px 0 0;
                padding: 16px;
            }

            .perfil-password-form form>div:last-child {
                align-items: stretch;
                border-radius: 0 0 10px 10px;
                flex-direction: column;
                gap: 10px;
                padding: 12px 16px;
            }

            .perfil-password-form form>div:last-child button {
                justify-content: center;
                margin: 0;
                width: 100%;
            }
        }
    </style>

    <div class="perfil-password-page">
        <div class="perfil-password-heading">
            <span class="perfil-password-heading-icon">
                <i class="fa-solid fa-lock"></i>
            </span>
            <div>
                <h1>Cambiar contraseña</h1>
                <p>Ingrese su contraseña actual y defina una nueva contraseña para su cuenta.</p>
            </div>
        </div>

        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
            @livewire('profile.update-password-form')
        @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">
                El cambio de contraseña no está habilitado en la configuración del sistema.
            </div>
        @endif
    </div>
</x-admin-layout>
