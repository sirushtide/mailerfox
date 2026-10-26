import React, { useRef, useState } from 'react';

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

async function uploadImageFile(uploadUrl, file) {
    if (!uploadUrl || !file) {
        return '';
    }

    const formData = new FormData();
    formData.append('image', file);

    const res = await fetch(uploadUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            Accept: 'application/json',
        },
        body: formData,
    });

    const json = await res.json().catch(() => null);
    if (!res.ok) {
        throw new Error((json && json.message) ? json.message : 'Upload failed');
    }

    const url = json && typeof json.url === 'string'
        ? json.url
        : (json && typeof json.filelink === 'string' ? json.filelink : '');
    if (!url) {
        throw new Error('Upload failed');
    }

    return url;
}

export { uploadImageFile };

function DropzoneUpload({ uploadUrl, onUploaded, accept = 'image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml' }) {
    const inputRef = useRef(null);
    const [isDragging, setIsDragging] = useState(false);
    const [isUploading, setIsUploading] = useState(false);

    const triggerPicker = () => {
        if (inputRef.current) inputRef.current.click();
    };

    const handleFile = async (file) => {
        if (!file) return;
        setIsUploading(true);
        try {
            const url = await uploadImageFile(uploadUrl, file);
            onUploaded(url);
        } catch (err) {
            window.alert(err && err.message ? err.message : 'Upload failed');
        } finally {
            setIsUploading(false);
            setIsDragging(false);
        }
    };

    return (
        <div className="w-full">
            <input
                ref={inputRef}
                type="file"
                accept={accept}
                className="hidden"
                onChange={(e) => {
                    const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
                    e.target.value = '';
                    handleFile(file);
                }}
            />

            <div
                role="button"
                tabIndex={0}
                onClick={triggerPicker}
                onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        triggerPicker();
                    }
                }}
                onDragEnter={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setIsDragging(true);
                }}
                onDragOver={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setIsDragging(true);
                }}
                onDragLeave={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setIsDragging(false);
                }}
                onDrop={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0] ? e.dataTransfer.files[0] : null;
                    handleFile(file);
                }}
                className={`w-full cursor-pointer select-none rounded-md border-2 border-dashed px-4 py-10 text-center transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 ${
                    isDragging
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                        : 'border-gray-200 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900/40 dark:hover:bg-gray-900/60'
                }`}
            >
                <div className="mx-auto flex max-w-md flex-col items-center gap-3">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="text-gray-300">
                        <path
                            d="M7.5 18.5h9a4 4 0 0 0 .7-7.94A5.5 5.5 0 0 0 6.45 8.7 3.5 3.5 0 0 0 7.5 18.5Z"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            strokeLinejoin="round"
                        />
                        <path d="M12 11v7" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                        <path d="M9.5 13.5 12 11l2.5 2.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>

                    <div className="text-base text-gray-600 dark:text-gray-300">
                        {isUploading ? (
                            <span>Uploading...</span>
                        ) : (
                            <span>
                                Drop files here or{' '}
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        triggerPicker();
                                    }}
                                    className="text-blue-600 underline underline-offset-2 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    browse
                                </button>
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

export function UploadButton({ uploadUrl, onUploaded, accept = 'image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml', buttonClassName = '' }) {
    const inputRef = useRef(null);

    return (
        <>
            <input
                ref={inputRef}
                type="file"
                accept={accept}
                className="hidden"
                onChange={async (e) => {
                    const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
                    e.target.value = '';
                    if (!file) return;

                    try {
                        const url = await uploadImageFile(uploadUrl, file);
                        onUploaded(url);
                    } catch (err) {
                        window.alert(err && err.message ? err.message : 'Upload failed');
                    }
                }}
            />
            <button
                type="button"
                onClick={() => {
                    if (inputRef.current) inputRef.current.click();
                }}
                className={`inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800 ${
                    buttonClassName || ''
                }`}
            >
                Upload
            </button>
        </>
    );
}

export function ImageUrlUploadControl({
    value,
    onChange,
    uploadUrl,
    onUploaded,
    onRemove,
    placeholder = 'Enter image URL',
    accept = 'image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml',
    withUrlInput = true,
}) {
    const inputRef = useRef(null);
    const dragCounter = useRef(0);
    const [isDragging, setIsDragging] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const [imageError, setImageError] = useState(false);
    const [imageLoaded, setImageLoaded] = useState(false);
    const [urlInput, setUrlInput] = useState(typeof value === 'string' ? value : '');
    const [urlLoading, setUrlLoading] = useState(false);
    const [urlError, setUrlError] = useState(false);

    const handleUploaded = (url) => {
        if (typeof onUploaded === 'function') {
            onUploaded(url);
            return;
        }
        onChange(url);
    };

    React.useEffect(() => {
        setImageError(false);
        setImageLoaded(false);
    }, [value]);

    React.useEffect(() => {
        setUrlInput(typeof value === 'string' ? value : '');
        setUrlError(false);
    }, [value]);

    const commitUrl = () => {
        if (urlLoading) return;
        const next = urlInput.trim();
        if (next === '') {
            setUrlError(false);
            return;
        }
        if (next === (typeof value === 'string' ? value : '')) {
            setUrlError(false);
            return;
        }

        setUrlError(false);
        setUrlLoading(true);
        const probe = new Image();
        probe.onload = () => {
            setUrlLoading(false);
            handleUploaded(next);
        };
        probe.onerror = () => {
            setUrlLoading(false);
            setUrlError(true);
        };
        probe.src = next;
    };

    const triggerPicker = () => {
        if (inputRef.current && !isUploading) inputRef.current.click();
    };

    const handleFile = async (file) => {
        if (!file) return;
        if (!uploadUrl) return;

        setIsUploading(true);
        try {
            const url = await uploadImageFile(uploadUrl, file);
            handleUploaded(url);
        } catch (err) {
            window.alert(err && err.message ? err.message : 'Upload failed');
        } finally {
            setIsUploading(false);
            setIsDragging(false);
            dragCounter.current = 0;
        }
    };

    const handleRemove = () => {
        if (typeof onRemove === 'function') {
            onRemove();
            return;
        }
        handleUploaded('');
    };

    const handleDragEnter = (e) => {
        e.preventDefault();
        e.stopPropagation();
        dragCounter.current += 1;
        if (e.dataTransfer && e.dataTransfer.items && e.dataTransfer.items.length > 0) {
            setIsDragging(true);
        }
    };

    const handleDragLeave = (e) => {
        e.preventDefault();
        e.stopPropagation();
        dragCounter.current -= 1;
        if (dragCounter.current <= 0) {
            dragCounter.current = 0;
            setIsDragging(false);
        }
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0] ? e.dataTransfer.files[0] : null;
        handleFile(file);
    };

    const showPreview = typeof value === 'string' && value.trim() !== '';

    return (
        <div className="space-y-2">
            <input
                ref={inputRef}
                type="file"
                accept={accept}
                className="hidden"
                onChange={(e) => {
                    const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
                    e.target.value = '';
                    handleFile(file);
                }}
            />

            {showPreview ? (
                <div className="rounded-lg border-2 border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800">
                    <div className="relative w-full h-48 flex items-center justify-center p-4">
                        <div className="absolute right-2 top-2 z-10 flex items-center gap-2">
                            <button
                                type="button"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    triggerPicker();
                                }}
                                disabled={!uploadUrl || isUploading}
                                className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white/90 text-gray-700 shadow-sm hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-900/80 dark:text-gray-200"
                                title="Change"
                                aria-label="Change"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg>
                            </button>

                            <button
                                type="button"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    handleRemove();
                                }}
                                disabled={isUploading}
                                className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-white/90 text-red-700 shadow-sm hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed dark:border-red-900/40 dark:bg-gray-900/80 dark:text-red-300"
                                title="Remove"
                                aria-label="Remove"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M10 11v6" />
                                    <path d="M14 11v6" />
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                </svg>
                            </button>
                        </div>

                        {!imageLoaded && !imageError ? (
                            <div className="absolute inset-0 flex items-center justify-center">
                                <svg className="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        ) : null}

                        {imageError ? (
                            <div className="text-gray-400 dark:text-gray-500 text-sm text-center">
                                <svg className="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Failed to load image
                            </div>
                        ) : (
                            <img
                                src={value}
                                alt="Uploaded"
                                className="max-w-full max-h-full object-contain"
                                style={{ display: imageLoaded ? 'block' : 'none' }}
                                onLoad={() => {
                                    setImageLoaded(true);
                                    setImageError(false);
                                }}
                                onError={() => {
                                    setImageError(true);
                                    setImageLoaded(false);
                                }}
                            />
                        )}
                    </div>
                </div>
            ) : (
                <div
                    role="button"
                    tabIndex={0}
                    onClick={triggerPicker}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            triggerPicker();
                        }
                    }}
                    onDragEnter={handleDragEnter}
                    onDragLeave={handleDragLeave}
                    onDragOver={handleDragOver}
                    onDrop={handleDrop}
                    className={`w-full cursor-pointer select-none rounded-md border-2 border-dashed px-4 py-10 text-center transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 ${
                        isDragging
                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                            : 'border-gray-200 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900/40 dark:hover:bg-gray-900/60'
                    }`}
                >
                    <div className="mx-auto flex max-w-md flex-col items-center gap-3">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="text-gray-300">
                            <path
                                d="M7.5 18.5h9a4 4 0 0 0 .7-7.94A5.5 5.5 0 0 0 6.45 8.7 3.5 3.5 0 0 0 7.5 18.5Z"
                                stroke="currentColor"
                                strokeWidth="1.5"
                                strokeLinejoin="round"
                            />
                            <path d="M12 11v7" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                            <path d="M9.5 13.5 12 11l2.5 2.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>

                        <div className="text-base text-gray-600 dark:text-gray-300">
                            {isUploading ? (
                                <span>Uploading...</span>
                            ) : (
                                <span>
                                    Drop files here or{' '}
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            triggerPicker();
                                        }}
                                        className="text-blue-600 underline underline-offset-2 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                    >
                                        browse
                                    </button>
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {withUrlInput ? (
                <div>
                    <div className="flex items-center gap-2">
                        <input
                            type="text"
                            value={urlInput}
                            onChange={(e) => {
                                setUrlInput(e.target.value);
                                if (urlError) setUrlError(false);
                            }}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    commitUrl();
                                }
                            }}
                            onBlur={commitUrl}
                            placeholder={placeholder}
                            className="flex-1 min-w-0 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        />
                        <button
                            type="button"
                            onClick={commitUrl}
                            disabled={urlLoading}
                            className="inline-flex h-9 shrink-0 items-center justify-center rounded-md border border-gray-200 dark:border-gray-700 px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            {urlLoading ? (
                                <span className="inline-flex items-center gap-2">
                                    <svg className="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading…
                                </span>
                            ) : (
                                'Load'
                            )}
                        </button>
                    </div>
                    {urlError ? (
                        <p className="mt-1 text-xs text-red-600 dark:text-red-400">Couldn't load that image URL. Please check the link and try again.</p>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}

export function MultiImageUploadControl({
    value,
    onChange,
    uploadUrl,
    accept = 'image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml',
}) {
    const inputRef = useRef(null);
    const actionRef = useRef({ mode: 'new', index: -1 });
    const [isUploading, setIsUploading] = useState(false);

    const images = Array.isArray(value) ? value : [];
    const normalized = images
        .map((it) => {
            if (typeof it === 'string') return { url: it };
            if (it && typeof it === 'object') return { url: String(it.url || '') };
            return { url: '' };
        })
        .filter((it) => typeof it.url === 'string' && it.url.trim() !== '');

    const setImages = (next) => {
        if (typeof onChange === 'function') {
            onChange(next);
        }
    };

    const triggerPicker = (mode, index) => {
        actionRef.current = { mode: mode === 'replace' ? 'replace' : 'new', index: Number.isFinite(Number(index)) ? Number(index) : -1 };
        if (!uploadUrl) {
            const url = window.prompt('Image URL');
            if (!url) return;
            if (actionRef.current.mode === 'replace' && actionRef.current.index >= 0) {
                const next = normalized.slice();
                next[actionRef.current.index] = { url };
                setImages(next);
            } else {
                setImages([...normalized, { url }]);
            }
            return;
        }
        if (inputRef.current && !isUploading) inputRef.current.click();
    };

    const handleFile = async (file) => {
        if (!file) return;
        if (!uploadUrl) return;
        setIsUploading(true);
        try {
            const url = await uploadImageFile(uploadUrl, file);
            const next = normalized.slice();
            if (actionRef.current.mode === 'replace' && actionRef.current.index >= 0 && actionRef.current.index < next.length) {
                next[actionRef.current.index] = { url };
                setImages(next);
            } else {
                setImages([...next, { url }]);
            }
        } catch (err) {
            window.alert(err && err.message ? err.message : 'Upload failed');
        } finally {
            setIsUploading(false);
        }
    };

    const removeAt = (idx) => {
        const next = normalized.filter((_, i) => i !== idx);
        setImages(next);
    };

    return (
        <div className="space-y-2">
            <input
                ref={inputRef}
                type="file"
                accept={accept}
                className="hidden"
                onChange={(e) => {
                    const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
                    e.target.value = '';
                    handleFile(file);
                }}
            />

            <div className="grid grid-cols-3 gap-2">
                {normalized.map((img, idx) => (
                    <div
                        key={idx}
                        role="button"
                        tabIndex={0}
                        onClick={() => triggerPicker('replace', idx)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                triggerPicker('replace', idx);
                            }
                        }}
                        className="relative aspect-square w-full flex items-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow-sm hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500"
                        title="Click to change"
                    >
                        {img.url ? (
                            <img src={img.url} alt="" className="w-full object-cover" />
                        ) : (
                            <div className="w-full flex items-center justify-center text-xs text-gray-400">Empty</div>
                        )}

                        <button
                            type="button"
                            onClick={(e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                removeAt(idx);
                            }}
                            className="absolute right-0 top-0 inline-flex h-6 w-6 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 shadow hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            title="Remove"
                            aria-label="Remove"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2-icon lucide-trash-2"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </div>
                ))}

                <button
                    type="button"
                    onClick={() => triggerPicker('new', -1)}
                    disabled={isUploading}
                    className="aspect-square w-full rounded-lg border border-dashed border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                    title="Add image"
                    aria-label="Add image"
                >
                    <div className="flex h-full w-full items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                    </div>
                </button>
            </div>
        </div>
    );
}
