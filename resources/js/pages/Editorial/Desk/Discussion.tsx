import AppLayout from '@/layouts/app-layout';
import DiscussionThread from '@/components/DiscussionThread';

// Definisikan tipe props yang diterima dari Inertia
interface DiscussionPageProps {
    submissionId: number;
    discussions: any[];
}

export default function Discussion({ submissionId, discussions }: DiscussionPageProps) {
    return (
        <AppLayout>
            <div className="p-6">
                <h1 className="mb-6 text-2xl font-bold">
                    Editorial Discussion
                </h1>

                {/* Teruskan submissionId dan data discussions asli dari backend */}
                <DiscussionThread
                    submissionId={submissionId}
                    discussions={discussions}
                    loading={false}
                />
            </div>
        </AppLayout>
    );
}