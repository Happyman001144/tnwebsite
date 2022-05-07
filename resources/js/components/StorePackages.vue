<template>
  <div class="row justify-content-center">
    <div class="col-md-3" data-aos="fade-up" v-for="pkg in packages">
      <div class="card hoverscale text-center">
        <div class="card-header">
          <h1 class="my-0 font-weight-normal">{{ pkg.name }}</h1>
        </div>
        <div class="card-body">
          <h2 class="card-title pricing-card-title mb-0">{{ pkg.cost }} <small class="text-muted">{{ 'package_listing_credits'|lang }}</small></h2>
          <h4 class="card-title pricing-card-title" v-if="pkg.valid_for"><small class="text-muted">{{ 'package_duration_for'|lang }}</small> {{ pkg.valid_for }} <small class="text-muted">{{ 'package_duration_days'|lang }}</small></h4>
          <h4 v-else><small>{{ 'package_listing_permanent'|lang }}</small></h4>
          <span v-if="pkg.image || pkg.short_description">
            <hr>
            <img :src="pkg.image" class="w-100"></img>
            <p v-html="pkg.short_description"></p>
          </span>
        </div>
        <div class="card-footer">
          <button type="button" class="btn btn-lg btn-block btn-outline-custom" @click="packageModal(pkg)">{{ 'package_details'|lang }}</button>
        </div>
      </div>
    </div>
    <b-modal centered size="lg" v-model="modalVisible" v-if="modalPackage">
      <h5 slot="modal-header" class="title mb-0">{{ modalPackage.name }}</h5>
      <h5 slot="modal-header" class="mb-0" style="cursor: pointer" @click="modalVisible = false"><i class="far fa-times-circle"></i></h5>
      <div slot="default" class="d-flex flex-column text-center align-items-center">
        <h1>{{ modalPackage.name }}</h1>
        <br>
        <p class="mb-0" v-html="modalPackage.description"></p>
      </div>
      <div slot="modal-footer" class="d-flex flex-row text-center align-items-center justify-content-around w-100">
        <h4 class="card-title pricing-card-title mb-0 w-25">{{ modalPackage.cost }} <small class="text-muted">{{ 'package_listing_credits'|lang }}</small></h4>
        <button class="btn btn-lg btn-outline-custom" @click="purchasePackage(modalPackage)"><i class="fas fa-shopping-cart mr-1"></i>{{ 'purchase'|lang }}</button>
        <h4 class="card-title pricing-card-title w-25" v-if="modalPackage.valid_for"><small class="text-muted">{{ 'package_duration_for'|lang }}</small> {{ modalPackage.valid_for }} <small class="text-muted">{{ 'package_duration_days'|lang }}</small></h4>
        <h4 class="w-25" v-else><small>{{ 'package_listing_permanent'|lang }}</small></h4>
      </div>
    </b-modal>
  </div>
</template>

<script>
  export default {
    props: {
      packages: Array,
      signedIn: Boolean
    },
    data: function() {
      return {
        modalVisible: false,
        modalPackage: null
      }
    },
    methods: {
      packageModal: function(pkg) {
          this.modalPackage = pkg;
          this.modalVisible = true;
      },
      purchasePackage: function(pkg) {
        this.modalVisible = false;
        if (!this.signedIn) {
          return swal({
            type: 'error',
            title: 'Not signed in',
            text: 'You need to be signed in to purchase packages.'
          });
        }
        swal({
          title: 'Confirm purchase',
          text: "Are you sure you want to purchase the "+pkg.name+" package for "+pkg.cost+" credits?",
          type: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, purchase it!'
        }).then((result) => {
          if (result.value) {
            $.post( "/store/packages/purchase/"+pkg.id)
              .done(function(res) {
                if (res.status === 'success') {
                  swal({
                    type: 'success',
                    title: 'Package purchased!',
                    text: 'It will be automatically redeemed for you next time you connect to the server.'
                  }).then(function() { location.reload(); });
                } else if (res.status === 'insufficient_funds') {
                  swal({
                    type: 'error',
                    title: 'Insufficient funds',
                    text: 'You need more credits to purchase this package.'
                  });
                } else if (res.status === 'purchase_limit_reached') {
                  swal({
                    type: 'error',
                    title: 'Purchase limit reached',
                    text: 'You cannot purchase more packages of this type.'
                  });
                } else {
                  swal({
                    type: 'error',
                    title: 'Unhandled error',
                    text: response
                  });
                }
              },'json');
          }
        })
      }
    }
  }
</script>
