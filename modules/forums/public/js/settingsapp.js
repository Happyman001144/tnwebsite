var app = new Vue({
el: '#forumsettingsapp',
components: {
  draggable
},
data: {
  draggableOptions: {filter: 'input,button', preventOnFilter: false, group: 'boards'},
  categories: categories,
  groups: groups,
  basePermissions: basePermissions
},
methods: {
  mysqlError: function (msg) {
    swal({
      type: 'error',
      title: 'MySQL error',
      text: msg
    })
  },
  highestCID: function () {
    var highest = 0;
    this.categories.forEach(function(item) {
      var cid = parseInt(item.cid);
      if (cid > highest) {
        highest = cid;
        return;
      }
    });
    return highest+1;
  },
  addCategory: function (event) {
    var newCid = this.highestCID();
    this.categories.push({cid: newCid, boards: []});
    this.updateCategory(newCid,'');
  },
  updateCategory: function (cid, name) {
    that = this;
    $.post("/admin/forums/category", { cid, name })
      .done(function(res) {
        if (res.status !== 'success') {
          that.mysqlError(response);
        }
      },'json');
  },
  removeCategory: function (rmID) {
    that = this;
    $.ajax({type: 'DELETE', url: "/admin/forums/category", data: { cid: rmID }, success: function(res) {
      if (res.status == 'success') {
        that.categories.forEach(function(item, index, object) {
          var cid = parseInt(item.cid);
          if (cid == rmID) {
            object.splice(index, 1);
            return;
          }
        });
      } else if (res.status == 'error_category_contains_boards') {
        swal({
          type: 'error',
          title: 'Failed to remove category',
          text: 'Categories with boards in them cannot be removed. Please move/delete the boards in this category first.'
        })
      } else {
        that.mysqlError(res);
      }
    }})
  },
  highestBID: function () {
    var highest = 0;
    this.categories.forEach(function(cat) {
      cat.boards.forEach(function(item) {
        var bid = parseInt(item.bid);
        if (bid > highest) {
          highest = bid;
          return;
        }
      })
    });
    return highest+1;
  },
  addBoard: function (event) {
    var newBid = this.highestBID();
    var cat =this.categories[this.categories.length-1]
    let perms = JSON.parse(JSON.stringify(basePermissions));
    cat.boards.push({bid: newBid, permissions_extended: perms});
    this.updateBoard(newBid, cat.cid, 'name', '');
  },
  updateBoard: function (bid, cid, field, value) {
    that = this;
    $.post("/admin/forums/board", { bid, cid, field, value })
      .done(function(res) {
        if (res.status !== 'success') {
          that.mysqlError(res);
        }
      },'json');
  },
  updatePermission: function (bid, gid, pname) {
    that = this;
    var newVal;
    that.categories.forEach(function(cat, index) {
      cat.boards.forEach(function(item, index, object) {
        if (parseInt(item.bid) == bid) {
          if (item.permissions_extended[gid] != null) {
            newVal = item.permissions_extended[gid][pname];
          }
          return;
        }
      })
    });
    $.post("/admin/forums/permission", { bid, gid, pname, newVal })
      .done(function(res) {
        if (res.status !== 'success') {
          that.mysqlError(res);
        }
      },'json');
  },
  updateBoardPositions: function () {
    that = this;
    var boardOrders = [];
    var orderIndex = 1;
    this.categories.forEach(function(cat, index) {
      cat.boards.forEach(function(item, index, object) {
        boardOrders.push({bid: parseInt(item.bid), cid: cat.cid, order: orderIndex});
        orderIndex++;
      })
    });
    $.post("/admin/forums/updateboardpositions", { boardOrders })
      .done(function(res) {
        if (res.status == 'success') {
        } else {
          that.mysqlError(res);
        }
      },'json');
  },
  removeBoard: function (rmID) {
    that = this;
    $.ajax({type: 'DELETE', url: "/admin/forums/board", data: { bid: rmID }, success: function(res) {
      if (res.status == 'success') {
        that.categories.forEach(function(cat, index) {
          cat.boards.forEach(function(item, index, object) {
            var bid = parseInt(item.bid);
            if (bid == rmID) {
              object.splice(index, 1);
              return;
            }
          })
        });
      } else if (res.status == 'error_board_contains_threads') {
        swal({
          type: 'error',
          title: 'Failed to remove board',
          text: 'Boards with threads in them cannot be removed. Please move/delete the threads in this board first.'
        })
      } else {
        that.mysqlError(response);
      }
    }})
  }
}
});

$('#forumsettingsapp').fadeIn('fast');
