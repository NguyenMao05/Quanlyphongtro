//File ni ae không cần đọc nha, file ni kiểu để thêm vài hiệu ứng cho đẹp thôi

// Khởi tạo Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Slider "Top phòng được quan tâm" (trang chủ)
    var featuredTrack = document.getElementById('featuredWeekTrack');
    if (featuredTrack) {
        var scrollStep = function () {
            var card = featuredTrack.querySelector('.featured-week-card');
            return card ? card.offsetWidth + 16 : 296;
        };
        var updateNav = function () {
            var maxScroll = featuredTrack.scrollWidth - featuredTrack.clientWidth - 2;
            document.querySelectorAll('[data-featured-scroll]').forEach(function (btn) {
                if (btn.getAttribute('data-featured-scroll') === 'prev') {
                    btn.disabled = featuredTrack.scrollLeft <= 2;
                } else {
                    btn.disabled = featuredTrack.scrollLeft >= maxScroll;
                }
            });
        };
        document.querySelectorAll('[data-featured-scroll]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dir = btn.getAttribute('data-featured-scroll') === 'next' ? 1 : -1;
                featuredTrack.scrollBy({ left: dir * scrollStep(), behavior: 'smooth' });
            });
        });
        featuredTrack.addEventListener('scroll', updateNav);
        window.addEventListener('resize', updateNav);
        updateNav();
    }

    // Khởi tạo tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Khởi tạo popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Hàm định dạng tiền tệ
function formatCurrency(value) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(value);
}

// Hộp thoại xác nhận chung (không dùng tên confirmDelete — tránh trùng modal xóa phòng)
function confirmDialog(message = 'Bạn có chắc chắn muốn xóa?') {
    return confirm(message);
}

// Hàm validate email
function validateEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Hàm validate phone
function validatePhone(phone) {
    var re = /^[0-9]{10,11}$/;
    return re.test(phone);
}

// Hàm hiển thị thông báo
function showNotification(message, type = 'info', timeout = 3000) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.main-content') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    if (timeout > 0) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, timeout);
    }
}

// Hàm xóa alert khi click vào nút close
document.addEventListener('DOMContentLoaded', function() {
    const alertButtons = document.querySelectorAll('.alert .btn-close');
    alertButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            this.closest('.alert').remove();
        });
    });
});

// Hàm load data bằng AJAX 
function loadData(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error('Error:', error));
}

// Hàm debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Hàm throttle
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Hàm copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Đã sao chép!', 'success', 2000);
    }).catch(err => {
        console.error('Error:', err);
        showNotification('Không thể sao chép!', 'danger', 2000);
    });
}

// Hàm format ngày tháng
function formatDate(date, format = 'dd/mm/yyyy') {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    
    const replacements = {
        'dd': day,
        'mm': month,
        'yyyy': year,
        'HH': hours,
        'MM': minutes
    };
    
    let result = format;
    for (let key in replacements) {
        result = result.replace(key, replacements[key]);
    }
    return result;
}

// Hàm tìm kiếm local
function searchLocal(query, dataArray, fields) {
    if (!query) return dataArray;
    
    const lowerQuery = query.toLowerCase();
    return dataArray.filter(item => {
        return fields.some(field => {
            const value = item[field] ? item[field].toString().toLowerCase() : '';
            return value.includes(lowerQuery);
        });
    });
}

// Hàm sort mảng
function sortArray(array, field, order = 'asc') {
    return array.sort((a, b) => {
        let aValue = a[field];
        let bValue = b[field];
        
        if (typeof aValue === 'string') {
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
        }
        
        if (order === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
}

// Hàm cuộn mới mục
function scrollToElement(element, offset = 0) {
    const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
    window.scrollTo({
        top: elementPosition - offset,
        behavior: 'smooth'
    });
}

// Hàm kiểm tra xem phần tử có trong viewport không
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Lazy loading hình ảnh
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
});

// Hàm lấy giá trị parameter từ URL
function getUrlParameter(name) {
    const url = new URL(window.location);
    return url.searchParams.get(name);
}

// Hàm set giá trị parameter trong URL
function setUrlParameter(name, value) {
    const url = new URL(window.location);
    url.searchParams.set(name, value);
    window.history.replaceState({}, '', url);
}

// Hàm xử lý form submit
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Có thể thêm validation tại đây
        });
    });
});

// Prevent double submit
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const buttons = form.querySelectorAll('button[type="submit"]');
            buttons.forEach(button => {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
            });
        });
    });
});

// Hàm hiển thị loading
function showLoading() {
    const loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) loading.remove();
}

// Export functions for use in HTML
window.formatCurrency = formatCurrency;
window.confirmDialog = confirmDialog;
window.validateEmail = validateEmail;
window.validatePhone = validatePhone;
window.showNotification = showNotification;
window.copyToClipboard = copyToClipboard;
window.scrollToElement = scrollToElement;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
