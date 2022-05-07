Vue.component('ace-editor', {
	template: '<div :id="id" style="height: 15em;"></div>',
  props: ['id', 'value', 'mode', 'theme'],
  mounted: function () {
  	var mode = this.mode || 'lua'
    var theme = this.theme || 'monokai'
		var editor = window.ace.edit(this.id)
    if (this.value != null) {
      editor.setValue(this.value, 1)
    }
    editor.session.setMode(`ace/mode/${mode}`)
    editor.setTheme(`ace/theme/${theme}`)
    editor.on('change', () => {
      this.$emit('input', editor.getValue())
    })
  }
})

if (typeof sv_loadingurl === 'undefined') { sv_loadingurl = 'asdasda'; }

var settingsapp = new Vue({
  el: '#settingsapp',
  components: {
    draggable
  },
  data: {
    cards: cards.data,
    cardType: cards.type,
    save_url: save_url,
    draggableOptions: {filter: 'input,button', preventOnFilter: false},
    tabs: cards.tabs,
    /* server/group select */
    servers: cards.servers,
    groups: cards.groups,
		/* store card server */
		server: cards.server,
    /* loading screen card sv_loadingurl */
    sv_loadingurl: sv_loadingurl
  },
  methods: {
    highestID: function () {
      var highest = 0;
      this.cards.forEach(function(item) {
        var id = parseInt(item.id);
        if (id > highest) {
          highest = id;
          return;
        }
      });
      return highest+1;
    },
    addCard: function () {
      var activeTab = null
      if (typeof this.tabs !== 'undefined') { activeTab = this.tabs[0] }
      if (this.cardType == 'storePackage') {
        this.cards.push({id: null, activeTab, perma_weapons: [""]});
      } else if (this.cardType == 'staffMember') {
        this.cards.push({steamid: $('#addTeamSteamid').val()});
      } else if (this.cardType == 'server') {
        this.cards.push({id: this.highestID(), token: '', activeTab});
      } else {
        this.cards.push({id: this.highestID(), activeTab});
      }
    },
		saveCard: function (card, index) {
			var postCard = JSON.parse(JSON.stringify(card));
			postCard.type = this.cardType
			postCard.order = index+1;
			if (this.cardType == 'storePackage') {
				delete postCard.activeTab
				postCard.server = this.server.id
				if (this.server.game == "gmod") {
					postCard.perma_weapons = postCard.perma_weapons.join();
				} else {
					delete postCard.perma_weapons
				}
			}
			$.ajax({ url: save_url, type: 'POST', data: postCard, success: function(res) {
				if (res.status === 'success') {
					if (card.id == null) {
						card.id = res.id;
					}
					swal({
						type: 'success',
						title: 'Changes saved',
						showConfirmButton: false,
						timer: 1000
					})
				} else {
					swal({
						type: 'error',
						title: 'MySQL error',
						text: response
					})
				}
			}});
		},
    removeCard: function(rmID) {
      that = this;
      swal({
        title: 'Confirm removal',
        text: 'Are you sure you want to remove '+this.cardType+' '+rmID+'?',
        type: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it'
      }).then((result) => {
        if (result.value) {
          $.ajax({ url: save_url, type: 'DELETE', data: { id: rmID, type: this.cardType }, success: function(res) {
            if (res.status === 'success') {
              that.cards.forEach(function(item, index, object) {
                var id = parseInt(item.id);

                if (that.cardType == 'staffMember') {
                  id = parseInt(item.steamid);
                }

                if (id == rmID) {
                  object.splice(index, 1);
                  return;
                }
              });
            } else {
              swal({
                type: 'error',
                title: 'MySQL error',
                text: response
              })
            }
          }});
        }
      });
    },
    selectTab(cardIndex, tab) {
      this.cards[cardIndex].activeTab = tab
    },
    generateToken: function (card) {
      card.token = '';
      var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";
      for (var i = 0; i < 25; i++)
      card.token += chars.charAt(Math.floor(Math.random() * chars.length));
    }
  }
});

$('#settingsapp').fadeIn('fast');
