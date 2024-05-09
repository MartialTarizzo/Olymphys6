slides = document.getElementsByClassName("containercarousel");
imgs = document.getElementsByClassName("img-fluid");
modal = document.getElementsByClassName("modal-gal");
modalImg = document.getElementsByClassName("modal-content-gal");
modalCap = document.getElementsByClassName("modal-caption-gal");
modalTitle=document.getElementsByClassName("modal-title")

$('#modal-gal').on('shown.bs.modal', function (event) {
    $('#enregistrer-image').trigger('focus');
    var diapo= $(event.relatedTarget); // image that triggered the modal
    var j = diapo.data("numimage");

    //var j= document.document.getElementById(clicked_id).value;
    var img = document.getElementById("photo".concat(j));
    var cap = document.getElementById("caption".concat(j));
    console.log(modalCap[0]);
    modalhref = document.getElementById("enregistrer-image");
    modalImg[0].src = img.src.replace('/thumbs/', '/');//Pour obtenir l'image HR sur la modale
    modalImg[0].style.height = "100%";
    modalImg[0].style.margin= "auto";
    modalTitle[0].innerHTML=document.getElementById("titre-equipe").innerHTML;
    if (cap != null) {

        modalCap[0].innerHTML = cap.innerHTML;
    }
    modalhref.href = modalImg[0].src;
    let array = modalImg[0].src.split('/');
    length = array.length;
    let file = array[length - 1];
    file = file.split('%20').join('_');
    modalhref.download = file;





})



$('#carousel').carousel({
    interval: 5000
})

$('.carousel .carousel-item').each(function () {
    var minPerSlide = 4;
    var next = $(this).next();
    if (!next.length) {
        next = $(this).siblings(':first');
    }
    next.children(':first-child').clone().appendTo($(this));

    for (var i = 0; i < minPerSlide; i++) {
        next = next.next();
        if (!next.length) {
            next = $(this).siblings(':first');
        }

        next.children(':first-child').clone().appendTo($(this));
    }
});