(function() {
    // ======================== 1. 深色模式切换 ========================
    const DARK_CLASS = 'dark-mode';
    const toggleBtn = document.getElementById('darkModeToggle');
    const icon = toggleBtn ? toggleBtn.querySelector('i') : null;
    
    function getInitialTheme() {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') return true;
        if (saved === 'light') return false;
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    
    function setDarkMode(isDark) {
        if (isDark) {
            document.body.classList.add(DARK_CLASS);
            if (icon) icon.className = 'fas fa-sun';
            localStorage.setItem('theme', 'dark');
        } else {
            document.body.classList.remove(DARK_CLASS);
            if (icon) icon.className = 'fas fa-moon';
            localStorage.setItem('theme', 'light');
        }
    }
    
    if (toggleBtn) {
        setDarkMode(getInitialTheme());
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            setDarkMode(!document.body.classList.contains(DARK_CLASS));
        });
    }
    
    // ======================== 2. 3D 倾斜特效 (仅桌面) ========================
    const tiltElements = document.querySelectorAll('.details-container, .highlight-card, .gallery-img, .profile-img');
    if (!('ontouchstart' in window)) {
        tiltElements.forEach(el => {
            if (el.dataset.tiltActive) return;
            el.dataset.tiltActive = 'true';
            const handleMove = (e) => {
                const rect = el.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width - 0.5;
                const y = (e.clientY - rect.top) / rect.height - 0.5;
                const maxRotate = (el.classList.contains('gallery-img') || el.classList.contains('profile-img')) ? 8 : 12;
                const rotateY = x * maxRotate;
                const rotateX = -y * maxRotate;
                el.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
                el.style.transition = 'transform 0.05s linear';
                el.style.boxShadow = '0 20px 30px -10px rgba(0,0,0,0.25)';
            };
            const handleLeave = () => {
                el.style.transform = '';
                el.style.boxShadow = '';
                el.style.transition = 'transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1)';
            };
            el.addEventListener('mousemove', handleMove);
            el.addEventListener('mouseleave', handleLeave);
        });
    }
    
    // ======================== 3. 卡片光晕特效 ========================
    const glowCards = document.querySelectorAll('.details-container, .highlight-card');
    glowCards.forEach(card => {
        if (card.querySelector('.mouse-glow')) return;
        const glowDiv = document.createElement('div');
        glowDiv.className = 'mouse-glow';
        glowDiv.style.position = 'absolute';
        glowDiv.style.top = '0';
        glowDiv.style.left = '0';
        glowDiv.style.width = '100%';
        glowDiv.style.height = '100%';
        glowDiv.style.borderRadius = 'inherit';
        glowDiv.style.pointerEvents = 'none';
        glowDiv.style.opacity = '0';
        glowDiv.style.transition = 'opacity 0.2s';
        glowDiv.style.zIndex = '1';
        glowDiv.style.background = 'radial-gradient(circle at center, rgba(255,245,200,0.2), transparent 70%)';
        card.style.position = 'relative';
        card.style.overflow = 'hidden';
        card.insertBefore(glowDiv, card.firstChild);
        
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            glowDiv.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255,245,180,0.3), rgba(255,200,100,0.1) 70%, transparent)`;
            glowDiv.style.opacity = '0.7';
        });
        card.addEventListener('mouseleave', () => {
            glowDiv.style.opacity = '0';
        });
        const updateGlowTheme = () => {
            const isDark = document.body.classList.contains(DARK_CLASS);
            if (isDark) {
                glowDiv.style.background = `radial-gradient(circle at 50% 50%, rgba(90,150,255,0.2), rgba(40,80,160,0.1) 70%, transparent)`;
            } else {
                glowDiv.style.background = `radial-gradient(circle at 50% 50%, rgba(255,245,180,0.2), rgba(255,200,100,0.1) 70%, transparent)`;
            }
        };
        const observer = new MutationObserver(() => updateGlowTheme());
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        updateGlowTheme();
    });
    
    // ======================== 4. 滚动渐现动画 ========================
    const fadeItems = document.querySelectorAll('.details-container, .highlight-card, .gallery-img');
    const appearObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                appearObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    fadeItems.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(18px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        appearObserver.observe(el);
    });
    
    // ======================== 5. 逻辑导航 (平滑滚动 + 高亮，兼容不存在锚点时的正常跳转) ========================
    const sectionIds = ['about', 'highlights', 'experience', 'projects', 'contact', 'profile'];
    // 获取所有可能包含锚点的导航链接（桌面 + 移动端）
    const desktopLinks = Array.from(document.querySelectorAll('.nav-links a[href^="#"], .nav-links a[href*="#"]'));
    const mobileLinks = Array.from(document.querySelectorAll('.mobile-menu ul a[href^="#"], .mobile-menu ul a[href*="#"]'));
    const allNavLinks = [...desktopLinks, ...mobileLinks];
    // 额外按钮（如 Hero 区的 Contact Me）
    const extraScrollButtons = Array.from(document.querySelectorAll('a[href="#contact"]:not(.nav-links a):not(.mobile-menu a)'));
    
    const NAV_OFFSET = 85;
    
    function setActiveLink(activeId) {
        allNavLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === `#${activeId}` || href === `cisc3003-IndAssgn-home.html#${activeId}`) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
    
    function smoothScrollToElement(element) {
        if (!element) return;
        const targetPosition = element.getBoundingClientRect().top + window.scrollY - NAV_OFFSET;
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }
    
    function handleNavClick(e) {
        const href = this.getAttribute('href');
        if (!href) return;
        // 提取锚点部分（支持 "cisc3003-IndAssgn-home.html#about" 或 "#about"）
        let targetId = null;
        if (href.startsWith('#')) {
            targetId = href.substring(1);
        } else if (href.includes('#') && (href.includes('cisc3003-IndAssgn-home.html') || href.includes('home.html'))) {
            targetId = href.split('#')[1];
        }
        
        if (targetId && sectionIds.includes(targetId)) {
            // 检查当前页面是否存在该锚点元素
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                e.preventDefault();
                smoothScrollToElement(targetElement);
                history.pushState(null, null, `#${targetId}`);
                setActiveLink(targetId);
                // 关闭移动端菜单
                const mobileDetails = document.querySelector('.mobile-menu');
                if (mobileDetails && mobileDetails.open) mobileDetails.removeAttribute('open');
                return;
            }
        }
        // 如果无法在当前页面处理（锚点不存在或不是内部锚点），则允许默认跳转（整页跳转）
        // 不做 preventDefault，让浏览器正常导航
    }
    
    function updateActiveSectionOnScroll() {
        let currentActive = '';
        const scrollPosition = window.scrollY + NAV_OFFSET + 20;
        for (let id of sectionIds) {
            const section = document.getElementById(id);
            if (section) {
                const offsetTop = section.offsetTop;
                const offsetBottom = offsetTop + section.offsetHeight;
                if (scrollPosition >= offsetTop && scrollPosition < offsetBottom) {
                    currentActive = id;
                    break;
                }
            }
        }
        if (!currentActive && window.scrollY < 150) currentActive = 'profile';
        if (!currentActive && (window.innerHeight + window.scrollY) >= document.body.scrollHeight - 50) currentActive = 'contact';
        if (currentActive) setActiveLink(currentActive);
    }
    
    function initNavigation() {
        allNavLinks.forEach(link => {
            link.addEventListener('click', handleNavClick);
        });
        extraScrollButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const contactSection = document.getElementById('contact');
                if (contactSection) {
                    smoothScrollToElement(contactSection);
                    history.pushState(null, null, '#contact');
                    setActiveLink('contact');
                }
            });
        });
        if (window.location.hash && window.location.hash.length > 1) {
            const targetId = window.location.hash.substring(1);
            if (sectionIds.includes(targetId)) {
                setTimeout(() => {
                    const targetElem = document.getElementById(targetId);
                    if (targetElem) {
                        smoothScrollToElement(targetElem);
                        setActiveLink(targetId);
                    }
                }, 100);
            }
        } else {
            setActiveLink('profile');
        }
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    updateActiveSectionOnScroll();
                    ticking = false;
                });
                ticking = true;
            }
        });
        updateActiveSectionOnScroll();
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavigation);
    } else {
        initNavigation();
    }
    
    const mobileMenuDetails = document.querySelector('.mobile-menu');
    if (mobileMenuDetails) {
        const mobileMenuLinks = mobileMenuDetails.querySelectorAll('ul a');
        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (mobileMenuDetails.open) mobileMenuDetails.removeAttribute('open');
            });
        });
    }
})();