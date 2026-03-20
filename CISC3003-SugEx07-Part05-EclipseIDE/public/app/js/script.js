// 獲取核心DOM元素
const header = document.querySelector('.header');
const hamburger = document.querySelector('.header__toggle');
const overlay = document.querySelector('.overlay');
const html = document.documentElement; // 取得 <html> 元素

// 定義通用函數：切換漢堡菜單狀態
function toggleMenu() {
  // 1. 切換導航欄的 .open 類（觸發漢堡菜單動畫）
  header.classList.toggle('open');
  
  // 2. 控制遮罩層淡入淡出
  if (header.classList.contains('open')) {
    overlay.classList.add('fade-in');
    // 鎖定滾動：同時為 html 和 body 添加 noscroll 類
    document.body.classList.add('noscroll');
    html.classList.add('noscroll');
  } else {
    overlay.classList.remove('fade-in');
    // 解鎖滾動：同時移除 html 和 body 的 noscroll 類
    document.body.classList.remove('noscroll');
    html.classList.remove('noscroll');
  }
}

// 漢堡菜單點擊事件
hamburger.addEventListener('click', toggleMenu);

// 點擊遮罩層關閉菜單
overlay.addEventListener('click', toggleMenu);

// 窗口尺寸變化時，自動關閉菜單
window.addEventListener('resize', () => {
  if (window.innerWidth >= 1024 && header.classList.contains('open')) {
    toggleMenu();
  }
});