

document.addEventListener("DOMContentLoaded", function () {
  initDropdown();
  initVideoHover();
  initAlertDismiss();
  initMobileMenu();
});


function initDropdown() {
  const btn = document.getElementById("userDropdownBtn");
  const menu = document.getElementById("userDropdownMenu");

  if (btn && menu) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      menu.classList.toggle("active");
    });

    document.addEventListener("click", function (e) {
      if (!menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.remove("active");
      }
    });
  }
}


function initVideoHover() {
  const videoCards = document.querySelectorAll(".video-card");

  videoCards.forEach((card) => {
    const video = card.querySelector("video[data-hover-play]");
    const thumbnail = card.querySelector(".video-thumbnail");

    if (video && thumbnail) {
      card.addEventListener("mouseenter", function () {
        thumbnail.classList.add("hidden");
        video.classList.remove("hidden");
        video.play().catch(() => {});
      });

      card.addEventListener("mouseleave", function () {
        video.pause();
        video.currentTime = 0;
        video.classList.add("hidden");
        thumbnail.classList.remove("hidden");
      });
    }

    
    const autoplayVideo = card.querySelector("video[data-autoplay-hover]");
    if (autoplayVideo) {
      card.addEventListener("mouseenter", function () {
        autoplayVideo.play().catch(() => {});
      });

      card.addEventListener("mouseleave", function () {
        autoplayVideo.pause();
        autoplayVideo.currentTime = 0;
      });
    }
  });
}


function initAlertDismiss() {
  const alerts = document.querySelectorAll(".alert");

  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0";
      alert.style.transform = "translateY(-10px)";
      setTimeout(() => alert.remove(), 300);
    }, 5000);
  });
}


function initMobileMenu() {
  const btn = document.getElementById("mobileMenuBtn");
  const nav = document.querySelector(".navbar-actions");

  if (btn && nav) {
    btn.addEventListener("click", function () {
      nav.classList.toggle("mobile-active");
    });
  }
}


function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  const wrapper = input.closest(".password-wrapper");
  const eyeOpen = wrapper.querySelector(".eye-open");
  const eyeClosed = wrapper.querySelector(".eye-closed");

  if (input.type === "password") {
    input.type = "text";
    if (eyeOpen) eyeOpen.style.display = "none";
    if (eyeClosed) eyeClosed.style.display = "block";
  } else {
    input.type = "password";
    if (eyeOpen) eyeOpen.style.display = "block";
    if (eyeClosed) eyeClosed.style.display = "none";
  }
}


function showLoginModal() {
  const modal = document.getElementById("loginModal");
  if (modal) {
    modal.classList.add("active");
  }
}


function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("active");
  }
}


document.addEventListener("click", function (e) {
  if (e.target.classList.contains("modal-backdrop")) {
    e.target.closest(".modal").classList.remove("active");
  }
});


document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    document.querySelectorAll(".modal.active").forEach((modal) => {
      modal.classList.remove("active");
    });
  }
});


function formatCurrency(input) {
  let value = input.value.replace(/\D/g, "");
  if (value) {
    value = parseInt(value).toLocaleString("id-ID");
    input.value = value;
  }
}


function confirmDelete(message) {
  return confirm(message || "Yakin ingin menghapus?");
}


function showToast(message, type = "success") {
  const toast = document.createElement("div");
  toast.className = `alert alert-${type}`;
  toast.style.cssText =
    "position: fixed; top: 100px; right: 20px; z-index: 9999; animation: slideIn 0.3s ease;";
  toast.innerHTML = `<span>${message}</span><button class="alert-close" onclick="this.parentElement.remove()">×</button>`;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = "0";
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}


const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    .alert { transition: opacity 0.3s, transform 0.3s; }
`;
document.head.appendChild(style);
