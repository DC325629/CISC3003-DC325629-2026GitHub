// js/script.js
// 获取DOM元素
const container = document.getElementById('container');
const signUpBtn = document.getElementById('signUp');
const signInBtn = document.getElementById('signIn');

// 注册按钮点击事件 - 切换到注册面板
signUpBtn.addEventListener('click', () => {
    container.classList.add('right-panel-active');
});

// 登录按钮点击事件 - 切换回登录面板
signInBtn.addEventListener('click', () => {
    container.classList.remove('right-panel-active');
});