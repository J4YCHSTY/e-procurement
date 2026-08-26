import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-lg font-semibold text-slate-900">
                    Profil Saya
                </h2>
            }
        >
            <Head title="Profile" />

            <div className="mx-auto max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card">
                    <DeleteUserForm className="max-w-xl" />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
