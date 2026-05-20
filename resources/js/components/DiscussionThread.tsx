import { useState } from 'react';
import { router } from '@inertiajs/react'; // <-- IMPORT INERTIA ROUTER

interface User {
    id: number;
    name: string;
}

interface Discussion {
    id: number;
    subject?: string;
    message: string;
    created_at: string;
    user: User;
}

interface DiscussionThreadProps {
    submissionId: number; // <-- TAMBAHKAN INI
    discussions: Discussion[];
    loading?: boolean;
}

export default function DiscussionThread({
    submissionId, // <-- TERIMA PROPS SUBMISSION ID
    discussions,
    loading = false,
}: DiscussionThreadProps) {
    const [activeReplyId, setActiveReplyId] = useState<number | null>(null);
    const [replyMessage, setReplyMessage] = useState('');
    const [isSending, setIsSending] = useState(false); // State untuk loading submit

    if (loading) {
        return (
            <div className="rounded-xl border bg-white p-6 shadow-sm">
                <p className="text-sm text-gray-500 animate-pulse">
                    Loading discussion...
                </p>
            </div>
        );
    }

    // Fungsi submit balasan ke backend Laravel
    const handleSendReply = (discussionId: number) => {
        if (!replyMessage.trim() || isSending) return;

        setIsSending(true);

        // Mengirim data ke route POST: /editorial/discussions/{submissionId}
        router.post(`/editorial/discussions/${submissionId}`, {
            message: replyMessage,
            subject: null // Kirim null jika ini adalah reply thread, atau sesuaikan kebutuhan bisnis
        }, {
            onSuccess: () => {
                // Reset state form jika berhasil
                setReplyMessage('');
                setActiveReplyId(null);
            },
            onFinish: () => {
                setIsSending(false);
            }
        });
    };

    return (
        <div className="rounded-xl border bg-white shadow-sm">
            <div className="border-b p-4 bg-gray-50/50 rounded-t-xl">
                <h3 className="text-lg font-semibold text-gray-800">
                    Discussion Thread
                </h3>
            </div>

            <div className="space-y-4 p-4">
                {discussions.length > 0 ? (
                    discussions.map((discussion) => (
                        <div
                            key={discussion.id}
                            className="rounded-lg border p-4 transition hover:bg-gray-50/30 bg-white"
                        >
                            <div className="flex items-start justify-between">
                                <div>
                                    {discussion.subject && (
                                        <h4 className="text-base font-semibold text-gray-900">
                                            {discussion.subject}
                                        </h4>
                                    )}
                                    <p className="mt-1 text-sm font-medium text-gray-600">
                                        {discussion.user?.name || 'User Tidak Dikenal'}
                                    </p>
                                </div>

                                <span className="text-xs text-gray-400">
                                    {new Date(discussion.created_at).toLocaleString('id-ID', {
                                        dateStyle: 'medium',
                                        timeStyle: 'short'
                                    })}
                                </span>
                            </div>

                            <div className="mt-4 border-l-2 border-gray-100 pl-3">
                                <p className="text-sm leading-relaxed text-gray-700 whitespace-pre-wrap">
                                    {discussion.message}
                                </p>
                            </div>

                            <div className="mt-4 flex justify-end">
                                <button
                                    type="button"
                                    onClick={() => {
                                        if (activeReplyId === discussion.id) {
                                            setActiveReplyId(null);
                                        } else {
                                            setActiveReplyId(discussion.id);
                                        }
                                        setReplyMessage('');
                                    }}
                                    className={`rounded-lg border px-4 py-1.5 text-sm font-medium transition ${
                                        activeReplyId === discussion.id
                                            ? 'bg-gray-100 text-gray-800 border-gray-300'
                                            : 'text-gray-700 hover:bg-gray-100'
                                    }`}
                                >
                                    {activeReplyId === discussion.id ? 'Cancel' : 'Reply'}
                                </button>
                            </div>

                            {activeReplyId === discussion.id && (
                                <div className="mt-4 border-t pt-4 bg-gray-50/50 p-3 rounded-lg border border-dashed">
                                    <textarea
                                        rows={3}
                                        value={replyMessage}
                                        disabled={isSending}
                                        onChange={(e) => setReplyMessage(e.target.value)}
                                        placeholder="Tulis balasan editorial atau tanggapan revisi Anda di sini..."
                                        className="w-full rounded-lg border-gray-300 p-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border bg-white"
                                    />
                                    <div className="mt-2 flex justify-end space-x-2">
                                        <button
                                            type="button"
                                            disabled={isSending}
                                            onClick={() => {
                                                setActiveReplyId(null);
                                                setReplyMessage('');
                                            }}
                                            className="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-200 transition"
                                        >
                                            Batal
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => handleSendReply(discussion.id)}
                                            disabled={!replyMessage.trim() || isSending}
                                            className="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                        >
                                            {isSending ? 'Mengirim...' : 'Kirim Balasan'}
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))
                ) : (
                    <div className="py-10 text-center text-sm text-gray-500">
                        No discussions found.
                    </div>
                )}
            </div>
        </div>
    );
}