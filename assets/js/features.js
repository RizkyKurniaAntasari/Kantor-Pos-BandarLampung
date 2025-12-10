/**
 * Features JavaScript untuk Rating dan Comments
 */

// Global variables
var currentLocationFid = null;
var currentRating = { average: 0, count: 0 };
var OFFICE_IMAGE_MAP = {
    1: "/assets/images/1.png",
    2: "/assets/images/2.png",
    3: "/assets/images/3.png",
    4: "/assets/images/4.png",
    5: "/assets/images/5.png",
    6: "/assets/images/6.png",
    7: "/assets/images/7.png",
    8: "/assets/images/8.png",
    9: "/assets/images/9.png",
    10: "/assets/images/10.png",
    11: "/assets/images/11.png",
    12: "/assets/images/12.png",
    13: "/assets/images/13.png"
};
// Array untuk menyimpan semua image yang tersedia (1-13)
var AVAILABLE_OFFICE_IMAGES = [
    "/assets/images/1.png",
    "/assets/images/2.png",
    "/assets/images/3.png",
    "/assets/images/4.png",
    "/assets/images/5.png",
    "/assets/images/6.png",
    "/assets/images/7.png",
    "/assets/images/8.png",
    "/assets/images/9.png",
    "/assets/images/10.png",
    "/assets/images/11.png",
    "/assets/images/12.png",
    "/assets/images/13.png"
];


/**
 * Load rating untuk lokasi tertentu
 */
function loadRating(fid) {
    fetch(`api/rating.php?fid=${fid}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentRating = data.data;
                renderRating(fid);
            }
        })
        .catch(err => {
            console.error("Error loading rating:", err);
        });
}

/**
 * Render rating widget
 */
function renderRating(fid) {
    const ratingContainer = document.getElementById(`rating-container-${fid}`);
    if (!ratingContainer) return;
    
    const average = currentRating.average || 0;
    const count = currentRating.count || 0;
    const checkResult = canRate(fid);
    const remaining = checkResult.remaining || 0;
    
    let starsHTML = '';
    for (let i = 1; i <= 5; i++) {
        const activeClass = i <= Math.round(average) ? 'active' : '';
        const disabledClass = !checkResult.canRate ? 'disabled' : '';
        starsHTML += `<span class="star ${activeClass} ${disabledClass}" data-rating="${i}" onclick="submitRating(${fid}, ${i})" style="${!checkResult.canRate ? 'cursor: not-allowed; opacity: 0.5;' : ''}">★</span>`;
    }
    
    ratingContainer.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <div class="star-rating">
                ${starsHTML}
            </div>
            <div style="flex: 1;">
                <div style="font-size: 24px; font-weight: bold; color: #FFD23F; font-family: 'Space Grotesk', sans-serif;">
                    ${average.toFixed(1)}
                </div>
                <div style="font-size: 12px; color: #9CA3AF; font-family: 'JetBrains Mono', monospace;">
                    ${count} ${count === 1 ? 'rating' : 'ratings'}
                </div>
            </div>
        </div>
    `;
}

/**
 * Check if user can rate (batasi 1 rating per 24 jam per lokasi)
 */
function canRate(fid) {
    const storageKey = `rating_${fid}`;
    const ratingData = localStorage.getItem(storageKey);
    
    if (!ratingData) {
        return { canRate: true, remaining: 1 };
    }
    
    try {
        const data = JSON.parse(ratingData);
        const now = Date.now();
        const oneDay = 24 * 60 * 60 * 1000;
        
        let ratings = [];
        if (Array.isArray(data)) {
            ratings = data;
        } else if (data.ratings && Array.isArray(data.ratings)) {
            ratings = data.ratings;
        } else if (data.timestamp) {
            ratings = [data];
        }
        
        const validRatings = ratings.filter(r => {
            const timestamp = r.timestamp || r;
            return (now - timestamp) < oneDay;
        });
        
        if (validRatings.length >= 1) {
            const oldestRating = validRatings[0];
            const timestamp = oldestRating.timestamp || oldestRating;
            const timeUntilReset = oneDay - (now - timestamp);
            const hoursLeft = Math.ceil(timeUntilReset / (60 * 60 * 1000));
            return { 
                canRate: false, 
                remaining: 0,
                message: `Anda sudah memberikan rating hari ini. Coba lagi dalam ${hoursLeft} jam.`
            };
        }
        
        return { canRate: true, remaining: 1 - validRatings.length };
    } catch (e) {
        console.warn("Error parsing rating data, resetting:", e);
        localStorage.removeItem(storageKey);
        return { canRate: true, remaining: 1 };
    }
}

/**
 * Record rating di localStorage
 */
function recordRating(fid) {
    const storageKey = `rating_${fid}`;
    const newRating = {
        timestamp: Date.now()
    };
    
    localStorage.setItem(storageKey, JSON.stringify({
        ratings: [newRating],
        lastUpdated: Date.now()
    }));
}

/**
 * Submit rating dengan spam prevention
 */
function submitRating(fid, rating) {
    const checkResult = canRate(fid);
    
    if (!checkResult.canRate) {
        showNotification(checkResult.message || "Anda sudah memberikan rating cukup banyak. Coba lagi nanti.", "error");
        return;
    }
    
    const starsInModal = document.querySelectorAll(`.star[onclick*="submitRating(${fid}"]`);
    const starsInContainer = document.querySelectorAll(`#rating-container-${fid} .star`);
    const allStars = [...starsInModal, ...starsInContainer];
    
    allStars.forEach(star => {
        star.style.pointerEvents = 'none';
        star.style.opacity = '0.5';
        star.style.cursor = 'wait';
    });
    
    fetch('api/rating.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            fid: fid,
            rating: rating
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            recordRating(fid);
            showNotification("Rating berhasil disimpan!", "success");
            loadRating(fid);
            if (currentLocationFid == fid) {
                setTimeout(() => {
                    openLocationDetail(fid);
                }, 300);
            }
        } else {
            showNotification("Error: " + (data.message || "Gagal menyimpan rating"), "error");
            allStars.forEach(star => {
                star.style.pointerEvents = 'auto';
                star.style.opacity = '1';
                star.style.cursor = checkResult.canRate ? 'pointer' : 'not-allowed';
            });
        }
    })
    .catch(err => {
        console.error("Error submitting rating:", err);
        showNotification("Error: Gagal menyimpan rating", "error");
        allStars.forEach(star => {
            star.style.pointerEvents = 'auto';
            star.style.opacity = '1';
            star.style.cursor = checkResult.canRate ? 'pointer' : 'not-allowed';
        });
    });
}

/**
 * Update rating di popup
 */
function updatePopupRating(fid, ratingData) {
    const popupRatingEl = document.querySelector(`[data-fid="${fid}"] .popup-rating`);
    if (popupRatingEl && ratingData) {
        const average = ratingData.average || 0;
        popupRatingEl.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #FFD23F; font-size: 18px;">★</span>
                <span style="font-weight: 600; color: #FFD23F;">${average.toFixed(1)}</span>
                <span style="font-size: 12px; color: #9CA3AF;">(${ratingData.count || 0})</span>
            </div>
        `;
    }
}


/**
 * Load comments untuk lokasi tertentu
 */
function loadComments(fid) {
    fetch(`api/comments.php?fid=${fid}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderComments(fid, data.data || []);
            }
        })
        .catch(err => {
            console.error("Error loading comments:", err);
        });
}

/**
 * Render comments list
 */
function renderComments(fid, comments) {
    const commentsContainer = document.getElementById(`comments-container-${fid}`);
    if (!commentsContainer) return;
    
    if (!comments || comments.length === 0) {
        commentsContainer.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #9CA3AF; font-family: 'JetBrains Mono', monospace;">
                <p>Belum ada komentar</p>
            </div>
        `;
        return;
    }
    
    let commentsHTML = '<div style="max-height: 400px; overflow-y: auto;">';
    comments.forEach(comment => {
        let ratingHTML = '';
        if (comment.rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                stars += `<span style="color: ${i <= comment.rating ? '#FFD23F' : '#4B5563'}; font-size: 14px;">★</span>`;
            }
            ratingHTML = `<div style="margin-bottom: 8px;">${stars}</div>`;
        }
        
        commentsHTML += `
            <div class="comment-item">
                <div class="comment-header">
                    <div class="comment-author">${escapeHtml(comment.nama || 'Anonymous')}</div>
                    <div class="comment-date">${formatDate(comment.tanggal)}</div>
                </div>
                ${ratingHTML}
                <div class="comment-text">${escapeHtml(comment.komentar)}</div>
            </div>
        `;
    });
    commentsHTML += '</div>';
    
    commentsContainer.innerHTML = commentsHTML;
}

/**
 * Submit comment
 */
function submitComment(fid) {
    const nama = document.getElementById(`comment-name-${fid}`).value.trim();
    const komentar = document.getElementById(`comment-text-${fid}`).value.trim();
    const rating = document.getElementById(`comment-rating-${fid}`) ? parseInt(document.getElementById(`comment-rating-${fid}`).value) : null;
    
    if (!nama || !komentar) {
        showNotification("Nama dan komentar harus diisi", "error");
        return;
    }
    
    const formData = new FormData();
    formData.append('fid', fid);
    formData.append('nama', nama);
    formData.append('komentar', komentar);
    if (rating) {
        formData.append('rating', rating);
    }
    
    fetch('api/comments.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (rating) {
                showNotification("Komentar dan rating berhasil ditambahkan!", "success");
            } else {
                showNotification("Komentar berhasil ditambahkan!", "success");
            }
            document.getElementById(`comment-name-${fid}`).value = '';
            document.getElementById(`comment-text-${fid}`).value = '';
            if (document.getElementById(`comment-rating-${fid}`)) {
                document.getElementById(`comment-rating-${fid}`).value = '';
            }
            
            // Reload komentar
            loadComments(fid);
            
            // Jika ada rating di komentar, reload rating juga
            if (rating) {
                // Tunggu sebentar untuk memastikan backend sudah selesai update rating
                setTimeout(() => {
                    loadRating(fid);
                    
                    // Reload modal detail jika sedang terbuka untuk menampilkan rating terbaru
                    if (currentLocationFid == fid) {
                        setTimeout(() => {
                            openLocationDetail(fid);
                        }, 200);
                    }
                }, 400);
            } else {
                // Jika tidak ada rating, hanya reload komentar di modal jika sedang terbuka
                if (currentLocationFid == fid) {
                    loadComments(fid);
                }
            }
        } else {
            showNotification("Error: " + (data.message || "Gagal menambahkan komentar"), "error");
        }
    })
    .catch(err => {
        console.error("Error submitting comment:", err);
        showNotification("Error: Gagal menambahkan komentar", "error");
    });
}


/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 7) {
        return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
    } else if (days > 0) {
        return `${days} hari yang lalu`;
    } else if (hours > 0) {
        return `${hours} jam yang lalu`;
    } else if (minutes > 0) {
        return `${minutes} menit yang lalu`;
    } else {
        return 'Baru saja';
    }
}

function getDefaultOfficeImage(seed) {
    if (!AVAILABLE_OFFICE_IMAGES.length) {
        return '';
    }
    
    let index;
    if (typeof seed === 'number' && !isNaN(seed) && seed > 0) {
        index = (seed - 1) % AVAILABLE_OFFICE_IMAGES.length;
    } else {
        index = Math.floor(Math.random() * AVAILABLE_OFFICE_IMAGES.length);
    }
    
    return AVAILABLE_OFFICE_IMAGES[index];
}

function getLocationMedia(feature) {
    const props = (feature && feature.properties) || {};
    const rawId = props.fid ?? props.id;
    const officeId = Number(rawId);
    const candidates = [
        props.image,
        props.imageUrl,
        props.image_url,
        props.foto,
        props.foto_url,
        props.photo
    ];
    const mappedImage = !isNaN(officeId) && OFFICE_IMAGE_MAP[officeId] ? OFFICE_IMAGE_MAP[officeId] : null;
    const chosenImage = candidates.find(src => typeof src === 'string' && src.trim().length > 0) || mappedImage;
    const fallbackUrl = getDefaultOfficeImage(!isNaN(officeId) ? officeId : 0);
    let aboutText = props.about || props.deskripsi || props.description;
    if (!aboutText) {
        const name = props.nama || 'Kantor Pos';
        const area = props.lokasi || 'Bandar Lampung';
        aboutText = `Kantor Pos ${name} melayani pengiriman surat, paket, serta layanan pembayaran untuk masyarakat sekitar ${area}.`;
    }
    return {
        imageUrl: chosenImage || fallbackUrl,
        fallbackUrl,
        aboutText
    };
}

function getFeatureCoordinates(feature) {
    if (!feature || !feature.geometry || !Array.isArray(feature.geometry.coordinates)) {
        return { lat: null, lng: null };
    }
    const [lng, lat] = feature.geometry.coordinates;
    return {
        lat: typeof lat === 'number' ? lat : null,
        lng: typeof lng === 'number' ? lng : null
    };
}

function getGoogleMapsEmbedUrl(lat, lng, name) {
    if (typeof lat !== 'number' || typeof lng !== 'number') {
        return '';
    }
    const label = encodeURIComponent(name || 'Kantor Pos');
    return `https://www.google.com/maps?q=${lat},${lng}(${label})&z=17&hl=id&output=embed`;
}

function getGoogleMapsSearchUrl(lat, lng, name) {
    if (typeof lat !== 'number' || typeof lng !== 'number') {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(name || 'Kantor Pos Bandar Lampung')}`;
    }
    return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
}

function getGoogleMapsDirectionUrl(lat, lng) {
    if (typeof lat !== 'number' || typeof lng !== 'number') {
        return 'https://www.google.com/maps';
    }
    return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
}

function openImagePreview(url, title) {
    closeImagePreview();
    const overlay = document.createElement('div');
    overlay.id = 'imagePreviewOverlay';
    overlay.style.cssText = 'position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; display:flex; align-items:center; justify-content:center; padding:24px;';
    
    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'max-width:90vw; max-height:90vh; background:#0f172a; border-radius:18px; padding:20px; box-shadow:0 30px 80px rgba(0,0,0,0.6); position:relative;';
    
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Tutup preview gambar');
    closeBtn.style.cssText = 'position:absolute; top:12px; right:12px; width:36px; height:36px; border:none; border-radius:50%; background:rgba(15,23,42,0.8); color:white; font-size:20px; cursor:pointer;';
    closeBtn.textContent = '×';
    closeBtn.onclick = closeImagePreview;
    
    const titleEl = document.createElement('div');
    titleEl.textContent = title || 'Kantor Pos';
    titleEl.style.cssText = "margin-bottom:12px; color:#FFD23F; font-family:'Space Grotesk',sans-serif; font-weight:600; text-align:center;";
    
    const image = document.createElement('img');
    image.src = url;
    image.alt = title || 'Foto Kantor Pos';
    image.style.cssText = 'max-width:100%; max-height:70vh; border-radius:12px; object-fit:cover; display:block; margin:0 auto;';
    
    wrapper.appendChild(closeBtn);
    wrapper.appendChild(titleEl);
    wrapper.appendChild(image);
    overlay.appendChild(wrapper);
    
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeImagePreview();
        }
    });
    
    document.body.appendChild(overlay);
    
    document.addEventListener('keydown', function escListener(e) {
        if (e.key === 'Escape') {
            document.removeEventListener('keydown', escListener);
            closeImagePreview();
        }
    }, { once: true });
}

function closeImagePreview() {
    const overlay = document.getElementById('imagePreviewOverlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Open location detail modal
 */
function openLocationDetail(fid) {
    currentLocationFid = fid;
    
    let feature = null;
    kantorLayer.eachLayer(function(l) {
        if (l.feature && (l.feature.properties.fid == fid || l.feature.properties.id == fid)) {
            feature = l.feature;
        }
    });
    
    if (!feature) {
        showNotification("Data tidak ditemukan", "error");
        return;
    }
    
    const modal = document.getElementById('modalLocationDetail');
    const isModalOpen = modal.classList.contains('active');
    
    if (isModalOpen) {
        const content = document.getElementById('locationDetailContent');
        content.innerHTML = `
            <div style="padding: 40px; text-align: center;">
                <div class="loading-dots" style="display: inline-flex; gap: 8px;">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <p style="color: #9CA3AF; margin-top: 16px; font-family: 'JetBrains Mono', monospace;">Memuat data...</p>
            </div>
        `;
    }
    
    const timestamp = new Date().getTime();
    Promise.all([
        fetch(`api/rating.php?fid=${fid}&_t=${timestamp}`)
            .then(r => {
                if (!r.ok) {
                    throw new Error(`HTTP error! status: ${r.status}`);
                }
                return r.json();
            })
            .catch(err => {
                console.error("Error loading rating:", err);
                return { success: false, error: true, data: { average: 0, count: 0, distribution: { '1': 0, '2': 0, '3': 0, '4': 0, '5': 0 }, ratings: [] } };
            }),
        fetch(`api/comments.php?fid=${fid}&_t=${timestamp}`)
            .then(r => {
                if (!r.ok) {
                    throw new Error(`HTTP error! status: ${r.status}`);
                }
                return r.json();
            })
            .catch(err => {
                console.error("Error loading comments:", err);
                return { success: false, error: true, data: [] };
            })
    ]).then(([ratingData, commentsData]) => {
        // Handle error responses
        if (ratingData.error || !ratingData.success) {
            console.warn("Rating data error:", ratingData);
            ratingData = { success: true, data: { average: 0, count: 0, distribution: { '1': 0, '2': 0, '3': 0, '4': 0, '5': 0 }, ratings: [] } };
        }
        if (commentsData.error || !commentsData.success) {
            console.warn("Comments data error:", commentsData);
            commentsData = { success: true, data: [] };
        }
        
        renderLocationDetail(feature, ratingData, commentsData);
        if (!isModalOpen) {
            modal.classList.add('active');
        }
    }).catch(err => {
        console.error("Error loading location detail:", err);
        showNotification("Error: Gagal memuat detail lokasi", "error");
        if (isModalOpen) {
            const content = document.getElementById('locationDetailContent');
            content.innerHTML = `
                <div style="padding: 40px; text-align: center; color: #DC2626;">
                    <p>Gagal memuat data. Silakan coba lagi.</p>
                </div>
            `;
        }
    });
}

/**
 * Render location detail modal
 */
function renderLocationDetail(feature, ratingData, commentsData) {
    const content = document.getElementById('locationDetailContent');
    const rating = ratingData.success ? ratingData.data : { average: 0, count: 0 };
    const comments = commentsData.success ? (commentsData.data || []) : [];
    const props = feature.properties || {};
    
    const fid = props.fid || props.id;
    const checkResult = canRate(fid);
    const remaining = checkResult.remaining || 0;
    const media = getLocationMedia(feature);
    const mediaImageId = `location-image-${fid}`;
    const previewButtonId = `preview-image-${fid}`;
    const displayName = props.nama || 'Kantor Pos';
    const lokasiDisplay = props.lokasi || 'Bandar Lampung';
    const { lat, lng } = getFeatureCoordinates(feature);
    const googleEmbedUrl = getGoogleMapsEmbedUrl(lat, lng, displayName);
    const googleSearchUrl = getGoogleMapsSearchUrl(lat, lng, displayName);
    const googleDirectionUrl = getGoogleMapsDirectionUrl(lat, lng);
    
    let starsHTML = '';
    for (let i = 1; i <= 5; i++) {
        const activeClass = i <= Math.round(rating.average) ? 'active' : '';
        const disabledClass = !checkResult.canRate ? 'disabled' : '';
        starsHTML += `<span class="star ${activeClass} ${disabledClass}" onclick="submitRating(${fid}, ${i})" style="${!checkResult.canRate ? 'cursor: not-allowed; opacity: 0.5;' : ''}">★</span>`;
    }
    
    let commentsHTML = '';
    comments.forEach(comment => {
        let ratingStars = '';
        if (comment.rating) {
            for (let i = 1; i <= 5; i++) {
                ratingStars += `<span style="color: ${i <= comment.rating ? '#FFD23F' : '#4B5563'}; font-size: 14px;">★</span>`;
            }
        }
        
        commentsHTML += `
            <div class="comment-item">
                <div class="comment-header">
                    <div class="comment-author">${escapeHtml(comment.nama || 'Anonymous')}</div>
                    <div class="comment-date">${formatDate(comment.tanggal)}</div>
                </div>
                ${ratingStars ? `<div style="margin-bottom: 8px;">${ratingStars}</div>` : ''}
                <div class="comment-text">${escapeHtml(comment.komentar)}</div>
            </div>
        `;
    });
    
    content.innerHTML = `
        <div style="padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px dashed rgba(255, 107, 53, 0.3);">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #FF6B35, #FFD23F); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 30px rgba(255, 107, 53, 0.5);">
                    <svg style="width: 36px; height: 36px;" fill="white" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                </div>
                <div style="flex: 1;">
                    <h1 style="font-size: 24px; font-weight: 700; color: #FFD23F; margin-bottom: 4px; font-family: 'Space Grotesk', sans-serif;">
                        ${escapeHtml(displayName)}
                    </h1>
                    <p style="font-size: 12px; color: #888; font-family: 'JetBrains Mono', monospace;">
                        ID: ${fid}
                    </p>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 24px;">
                <div style="flex: 1 1 320px;">
                    <div style="position: relative; border-radius: 18px; overflow: hidden; min-height: 260px; aspect-ratio: 4 / 3; box-shadow: 0 25px 45px rgba(0,0,0,0.35); background: rgba(255,255,255,0.03);">
                        <img id="${mediaImageId}" src="${media.imageUrl}" alt="Foto ${escapeHtml(displayName)}" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                        <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 40%, rgba(0,0,0,0.85) 100%);"></div>
                    </div>
                    <button type="button" id="${previewButtonId}" style="margin-top: 16px; width: 100%; justify-content: center; background: linear-gradient(135deg, #FF6B35, #FF8C61); border: none; border-radius: 12px; padding: 12px 18px; color: white; font-weight: 600; cursor: pointer; font-family: 'Space Grotesk', sans-serif; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 10px 25px rgba(255,107,53,0.35);">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553 1.826A1 1 0 0120 12.764V19a2 2 0 01-2 2h-3m0 0H9m3 0v-4m0 4H6a2 2 0 01-2-2v-6.236a1 1 0 01.447-.838L9 10m0 0V6a3 3 0 013-3v0a3 3 0 013 3v4z"></path>
                        </svg>
                        <span>Lihat Foto</span>
                    </button>
                </div>
                <div style="flex: 1 1 260px; background: rgba(255,255,255,0.03); border-radius: 18px; padding: 20px; border: 1px solid rgba(255, 107, 53, 0.2);">
                    <h3 style="font-size: 18px; font-weight: 600; color: #FFD23F; margin-bottom: 12px; font-family: 'Space Grotesk', sans-serif;">
                        Tentang Kantor
                    </h3>
                    <p style="color: #D1D5DB; line-height: 1.6; font-size: 14px;">
                        ${escapeHtml(media.aboutText)}
                    </p>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 18px; font-weight: 600; color: #FFD23F; margin-bottom: 12px; font-family: 'Space Grotesk', sans-serif;">
                    Google Maps
                </h3>
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div style="flex: 2 1 360px; background: rgba(255,255,255,0.03); border-radius: 18px; overflow: hidden; border: 1px solid rgba(255,255,255,0.06); min-height: 260px;">
                        ${googleEmbedUrl ? `
                            <iframe src="${googleEmbedUrl}" width="100%" height="100%" style="border:0; min-height: 260px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        ` : `
                            <div style="padding: 40px; text-align: center; color: #9CA3AF;">
                                <p>Koordinat tidak tersedia untuk menampilkan Google Maps.</p>
                            </div>
                        `}
                    </div>
                    <div style="flex: 1 1 220px; display: flex; flex-direction: column; gap: 12px;">
                        <a href="${googleSearchUrl}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 12px; background: linear-gradient(135deg, #0F9D58, #34A853); color: white; text-decoration: none; font-weight: 600; font-family: 'Space Grotesk', sans-serif; box-shadow: 0 12px 25px rgba(15, 157, 88, 0.35);">
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Telusuri di Google Maps
                        </a>
                        <a href="${googleDirectionUrl}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 12px; background: linear-gradient(135deg, #4285F4, #1A73E8); color: white; text-decoration: none; font-weight: 600; font-family: 'Space Grotesk', sans-serif; box-shadow: 0 12px 25px rgba(66, 133, 244, 0.35);">
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.257 3.099c.765-1.36 2.721-1.36 3.486 0l6.518 11.592c.75 1.335-.213 2.993-1.742 2.993H3.48c-1.528 0-2.492-1.658-1.742-2.993L8.257 3.1z"/>
                            </svg>
                            Rute ke Lokasi
                        </a>
                        <div style="padding: 14px 16px; border-radius: 12px; background: rgba(255,255,255,0.03); border: 1px dashed rgba(255,255,255,0.12); color: #D1D5DB; font-size: 13px; line-height: 1.5;">
                            Koordinat: ${lat && lng ? `<strong>${lat.toFixed(6)}, ${lng.toFixed(6)}</strong>` : 'Tidak tersedia'}
                            <br>
                            Data ini terhubung langsung ke tampilan Google Maps sehingga pengguna dapat mengecek detail lokasi dengan cepat.
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 24px; padding: 16px; background: rgba(255, 107, 53, 0.1); border-left: 4px solid #FF6B35; border-radius: 8px;">
                <div style="display: flex; align-items: start; gap: 10px; font-size: 14px; color: #ccc;">
                    <svg style="width: 20px; height: 20px; color: #FF6B35; margin-top: 2px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                        <div>
                            <strong style="color: #FFD23F; display: block; margin-bottom: 4px;">Lokasi</strong>
                            <span>${escapeHtml(lokasiDisplay)}</span>
                        </div>
                    </div>
                </div>
            
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 18px; font-weight: 600; color: #FFD23F; margin-bottom: 12px; font-family: 'Space Grotesk', sans-serif;">
                    Rating
                </h3>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <div class="star-rating">
                        ${starsHTML}
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 28px; font-weight: bold; color: #FFD23F; font-family: 'Space Grotesk', sans-serif;">
                            ${rating.average.toFixed(1)}
                        </div>
                        <div style="font-size: 12px; color: #9CA3AF; font-family: 'JetBrains Mono', monospace;">
                            ${rating.count} ${rating.count === 1 ? 'rating' : 'ratings'}
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 18px; font-weight: 600; color: #FFD23F; margin-bottom: 12px; font-family: 'Space Grotesk', sans-serif;">
                    Komentar
                </h3>
                <div style="max-height: 400px; overflow-y: auto; margin-bottom: 20px;">
                    ${commentsHTML || '<p style="text-align: center; color: #9CA3AF; padding: 40px;">Belum ada komentar</p>'}
                </div>
                
                <div style="background: rgba(255, 255, 255, 0.05); padding: 16px; border-radius: 12px;">
                    <div style="margin-bottom: 12px;">
                        <input type="text" id="comment-name-${fid}" placeholder="Nama Anda" style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(255, 107, 53, 0.3); border-radius: 8px; color: white; font-family: 'JetBrains Mono', monospace; margin-bottom: 8px;">
                    </div>
                    <div style="margin-bottom: 12px;">
                        <textarea id="comment-text-${fid}" placeholder="Tulis komentar..." rows="3" style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(255, 107, 53, 0.3); border-radius: 8px; color: white; font-family: 'JetBrains Mono', monospace; resize: vertical;"></textarea>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <select id="comment-rating-${fid}" class="form-input" style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05) !important; border: 2px solid rgba(255, 107, 53, 0.3); border-radius: 8px; color: white !important; font-family: 'JetBrains Mono', monospace;">
                            <option value="" style="background: rgba(26, 26, 46, 0.95) !important; color: #9CA3AF !important;">Pilih Rating (Opsional)</option>
                            <option value="5" style="background: rgba(26, 26, 46, 0.95) !important; color: #FFD23F !important;">★★★★★ (5)</option>
                            <option value="4" style="background: rgba(26, 26, 46, 0.95) !important; color: #FFD23F !important;">★★★★☆ (4)</option>
                            <option value="3" style="background: rgba(26, 26, 46, 0.95) !important; color: #FFD23F !important;">★★★☆☆ (3)</option>
                            <option value="2" style="background: rgba(26, 26, 46, 0.95) !important; color: #FFD23F !important;">★★☆☆☆ (2)</option>
                            <option value="1" style="background: rgba(26, 26, 46, 0.95) !important; color: #FFD23F !important;">★☆☆☆☆ (1)</option>
                        </select>
                    </div>
                    <button onclick="submitComment(${fid})" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #FF6B35, #FF8C61); color: white; border: none; border-radius: 8px; cursor: pointer; font-family: 'Space Grotesk', sans-serif; font-weight: 600;">
                        Kirim Komentar
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('modalDetailClose').onclick = closeLocationDetail;
    document.getElementById('modalLocationDetail').onclick = (e) => {
        if (e.target.id === 'modalLocationDetail') {
            closeLocationDetail();
        }
    };

    const previewButton = document.getElementById(previewButtonId);
    if (previewButton) {
        previewButton.addEventListener('click', function() {
            openImagePreview(media.imageUrl, displayName);
        });
    }

    const mediaImage = document.getElementById(mediaImageId);
    if (mediaImage) {
        const handleImageError = function handleImageError() {
            mediaImage.removeEventListener('error', handleImageError);
            mediaImage.src = media.fallbackUrl;
        };
        mediaImage.addEventListener('error', handleImageError);
    }
}

/**
 * Close location detail modal
 */
function closeLocationDetail() {
    const modal = document.getElementById('modalLocationDetail');
    modal.classList.remove('active');
}

window.submitRating = submitRating;
window.submitComment = submitComment;
window.openLocationDetail = openLocationDetail;
window.closeLocationDetail = closeLocationDetail;
window.openImagePreview = openImagePreview;
window.closeImagePreview = closeImagePreview;
