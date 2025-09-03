


jQuery(function($){
  let selected = null;

  $(".js-bsc-coupon.is-normal").on("click", function(){
    const points = $(this).data("points");
    const value  = $(this).data("value");
    selected = { points, value };

    $("#bscCouponText").text(
      `¿Deseas redimir ${points} puntos por un cupón de $${value}?`
    );
    $("#bscCouponModal").addClass("is-open");
  });

  $("#bscModalCancel").on("click", function(){
    $("#bscCouponModal").removeClass("is-open");
    selected = null;
  });

  $("#bscModalConfirm").on("click", function(){
    if(!selected) return;

    $.post(bsc_points.ajax_url, {
      action: "bsc_redeem_points",
      points: parseInt(String(selected.points).replace(/,/g, ""), 10),
      value: parseInt(String(selected.value).replace(/,/g, ""), 10),
      nonce: bsc_points.nonce
    }, function(resp){
      $("#bscCouponModal").removeClass("is-open");
      if(resp.success){
        alert("¡Cupón creado! Revisa tus cupones disponibles.");
        location.reload();
      } else {
        alert(resp.data.message || "Error al redimir puntos.");
      }
    });
  });

  // Close modal if clicking outside dialog
  $("#bscCouponModal").on("click", function(e){
    if(e.target === this) {
      $(this).removeClass("is-open");
      selected = null;
    }
  });
});
