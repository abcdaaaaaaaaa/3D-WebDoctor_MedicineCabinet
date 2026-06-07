let draggedBox = null;

const boxes = document.querySelectorAll('.box');
boxes.forEach(box => {
    box.addEventListener('dragstart', () => {
        draggedBox = box;
        box.style.animation = 'none';
        setTimeout(() => box.classList.add('dragging'), 0);
    });

    box.addEventListener('dragend', () => {
        draggedBox = null;
        box.classList.remove('dragging');
        box.style.animation = '';
    });
});

const shelves = document.querySelectorAll('.shelf');
shelves.forEach(shelf => {
    shelf.addEventListener('dragover', event => event.preventDefault());

    shelf.addEventListener('drop', () => {
        if (draggedBox) {
            shelf.appendChild(draggedBox);
            draggedBox.classList.add('placed');
        }
    });
});

const registerForm = document.getElementById('registerForm');
const orderInput = document.getElementById('orderInput');

registerForm.addEventListener('submit', (e) => {

    const shelves = document.querySelectorAll('.shelf');
    let orderArr = [];

    shelves.forEach(shelf => {
        const box = shelf.querySelector('.box');
        if(box) {
            orderArr.push(box.getAttribute('data-box'));
        }
    });

    if(orderArr.length !== 4) {
        e.preventDefault();
        alert('Lütfen tüm kutuları dolaba yerleştirin.');
        return false;
    }

    orderInput.value = orderArr.join('');
    console.log('Sıralama (order):', orderInput.value);
});
