
/*
document.addEventListener('DOMContentLoaded', function () {
  if (window.innerWidth > 768) return;

  const sliders = document.querySelectorAll('.bsc__slider');

  sliders.forEach((slider) => {
    const cards = Array.from(slider.children).filter((el) => {
      return !el.classList.contains('bsc__slider-title');
    });

    if (cards.length < 2) return;

    const firstCard = cards[0];
    const offset = firstCard.offsetWidth * 0.65;

    slider.scrollLeft = offset;
  });
});
*/