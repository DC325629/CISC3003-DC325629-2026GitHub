(function() {
    // ======================== 1. 深色模式切换 (保留原实现) ========================
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
    
    // ======================== 2. 3D 倾斜特效 (仅桌面，保留原逻辑) ========================
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
    
    // ======================== 3. 卡片光晕特效 (保留原实现) ========================
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
    
    // ======================== 4. 滚动渐现动画 (保留原逻辑) ========================
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
    
    // ======================== 5. 新增：逻辑导航模块 (平滑滚动 + 高亮 + 移动端菜单) ========================
    // 需要监听的区域ID（与导航href对应）
    const sectionIds = ['about', 'highlights', 'experience', 'projects', 'contact'];
    // 获取所有导航链接 (桌面 + 移动端)
    const desktopLinks = Array.from(document.querySelectorAll('.nav-links a[href^="#"]'));
    const mobileLinks = Array.from(document.querySelectorAll('.mobile-menu ul a[href^="#"]'));
    const allNavLinks = [...desktopLinks, ...mobileLinks];
    // 获取所有可能触发跳转的额外按钮 (例如 Hero 区域的 Contact Me 按钮)
    const extraScrollButtons = Array.from(document.querySelectorAll('a[href="#contact"]:not(.nav-links a):not(.mobile-menu a)'));
    
    // 导航栏高度偏移 (防止被固定导航栏遮挡)
    const NAV_OFFSET = 85;
    
    // 设置活动链接样式
    function setActiveLink(activeId) {
        allNavLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === `#${activeId}`) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
    
    // 平滑滚动到目标元素
    function smoothScrollToElement(element) {
        if (!element) return;
        const targetPosition = element.getBoundingClientRect().top + window.scrollY - NAV_OFFSET;
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }
    
    // 处理点击导航链接
    function handleNavClick(e) {
        const targetId = this.getAttribute('href'); // 例如 "#about"
        if (!targetId || !targetId.startsWith('#')) return;
        e.preventDefault();
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            smoothScrollToElement(targetElement);
            // 更新 URL 哈希 (不触发跳转)
            history.pushState(null, null, targetId);
            setActiveLink(targetId.substring(1));
            // 如果是移动端菜单，点击后关闭菜单
            const mobileDetails = document.querySelector('.mobile-menu');
            if (mobileDetails && mobileDetails.open) {
                mobileDetails.removeAttribute('open');
            }
        }
    }
    
    // 滚动监听: 根据当前视口更新活动导航
    function updateActiveSectionOnScroll() {
        let currentActive = '';
        const scrollPosition = window.scrollY + NAV_OFFSET + 20; // 稍微提前判定
    
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
        // 特殊处理: 滚动到顶部附近时高亮 About Me
        if (!currentActive && window.scrollY < 150) {
            currentActive = 'about';
        }
        // 滚动到底部时高亮 Contact Me
        if (!currentActive && (window.innerHeight + window.scrollY) >= document.body.scrollHeight - 50) {
            currentActive = 'contact';
        }
        if (currentActive) {
            setActiveLink(currentActive);
        }
    }
    
    // 初始化: 绑定事件、处理 Hash、滚动监听
    function initNavigation() {
        // 绑定导航链接点击事件
        allNavLinks.forEach(link => {
            link.addEventListener('click', handleNavClick);
        });
        // 绑定额外的按钮 (如 Hero 区的 Contact Me)
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
        // 处理页面加载时的 URL Hash
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
            } else {
                setActiveLink('about');
            }
        } else {
            setActiveLink('about');
        }
        // 监听滚动事件 (带节流)
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
        // 初始调用一次确保高亮正确
        updateActiveSectionOnScroll();
    }
    
    // 确保 DOM 完全加载后再执行导航初始化 (避免元素未找到)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavigation);
    } else {
        initNavigation();
    }
    
    // 额外: 移动端菜单点击任何链接后关闭菜单 (再次确保)
    const mobileMenuDetails = document.querySelector('.mobile-menu');
    if (mobileMenuDetails) {
        const mobileMenuLinks = mobileMenuDetails.querySelectorAll('ul a');
        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (mobileMenuDetails.open) {
                    mobileMenuDetails.removeAttribute('open');
                }
            });
        });
    }
    
})();