// $("document").ready(function () {
//   //table.destroy();
//   // Tablonun id'sini al
//   var tableId = $(".data-table").attr("id");

//   table = $(".data-table").DataTable({
//     //stateSave: true,

//     order: [],
//     scrollCollapse: true,
//     autoWidth: false,
//     responsive: true,
//     columnDefs: [
//       {
//         targets: "datatable-nosort",
//         orderable: true,
//       },
//     ],
//     lengthMenu: [
//       [10, 25, 50, -1],
//       [10, 25, 50, "Tümü"],
//     ],
//     pageLength:
//       tableId === "offerTable" || tableId === "serviceTable" ? -1 : 10,
//     language: {
//       info: "_TOTAL_ kayıttan _START_ - _END_ kayıt gösteriliyor.",
//       sLengthMenu: "Sayfada _MENU_ kayıt göster",
//       oPaginate: {
//         sFirst: "İlk",
//         sLast: "Son",
//         sNext: "Sonraki",
//         sPrevious: "Önceki",
//       },
//       searchPlaceholder: "Arama",
//       zeroRecords: "Hiç kayıt bulunamadı!",
//       infoEmpty: "",
//     },
//     initComplete: function () {
//       $(".data-table thead").append('<tr class="search-input-row"></tr>');
//       this.api()
//         .columns()
//         .every(function () {
//           let column = this;
//           let title = column.header().textContent;

//           if (title != "İşlem") {
//             // Create input element
//             let input = document.createElement("input");
//             input.placeholder = title;
//             input.classList.add("form-control");
//             input.setAttribute("autocomplete", "off");

//             // Append input element to the new row
//             $(".search-input-row").append($("<th>").append(input));

//             // Event listener for user input
//             $(input).on("keyup change", function () {
//               if (column.search() !== this.value) {
//                 column.search(this.value).draw();
//               }
//             });
//           } else {
//             // Eğer "İşlem" sütunuysa, boş bir th ekleyin
//             $(".search-input-row").append("<th></th>");
//           }
//         });
//       var state = table.state.loaded();
//       if (state) {
//         $("input", table.table().header()).each(function (index) {
//           var searchValue = state.columns[index].search.search;
//           if (searchValue) {
//             $(this).val(searchValue);
//           }
//         });
//       }
//     },
//   });
//   });





//   $(".data-table-export").DataTable({
//     scrollCollapse: true,
//     autoWidth: false,
//     responsive: true,
//     columnDefs: [
//       {
//         targets: "datatable-nosort",
//         orderable: false,
//       },
//     ],
//     lengthMenu: [
//       [10, 25, 50, -1],
//       [10, 25, 50, "Tümü"],
//     ],
//     language: {
//       info: "_TOTAL_ kayıttan _START_ - _END_ kayıt gösteriliyor.",
//       sLengthMenu: "Sayfada _MENU_ kayıt göster",
//       oPaginate: {
//         sFirst: "İlk",
//         sLast: "Son",
//         sNext: "Sonraki",
//         sPrevious: "Önceki",
//       },
//       searchPlaceholder: "Arama",
//     },
//     dom: "Bfrtip",
//     buttons: ["copy", "csv", "pdf", "xls", "print"],
//   });
//   var table = $(".select-row").DataTable();
//   $(".select-row tbody").on("click", "tr", function () {
//     if ($(this).hasClass("selected")) {
//       $(this).removeClass("selected");
//     } else {
//       table.$("tr.selected").removeClass("selected");
//       $(this).addClass("selected");
//     }
//   });
//   var multipletable = $(".multiple-select-row").DataTable();
//   $(".multiple-select-row tbody").on("click", "tr", function () {
//     $(this).toggleClass("selected");
//   });
// });






let table;
$(document).ready(function () {
  //   // Tablonun id'sini al
  var tableId = $(".data-table").attr("id");
  
  // Skip initialization for tables managed elsewhere
  if (tableId === "customerlist" || tableId === "service-table" || tableId === "itemsTable" || tableId === "tblProducts" || tableId === "reportTable") {
    return;
  }
  
  table = $(".data-table").not("#customerlist, #service-table, #itemsTable, #tblProducts, #reportTable").DataTable({
    autoWidth: false,
     lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Tümü"],
    ],
    pageLength: (tableId === "offerTable2" || tableId === "serviceTable") ? -1 : 10,
    
    layout: {
      // bottomStart: "pageLength",
      bottomEnd: "paging",
      topStart: "buttons",
      topEnd: null,
    },
    language: {
      url: "include/js/tr.json",
    },

    buttons: [
      {
        extend: "excel",
        className: "d-none",
        exportOptions: {
          columns: ":visible:not(.no-export)",
        },
      },
    ],

    stateSave: true,


    // Tablo özelliklerini belirle
    ...getTableSpecificOptions(),

    initComplete: function (settings, json) {
      if (window.App && window.App.TableFilter) {
        App.TableFilter.attachToTable(this.api().table().node());
      }
    },
  });
});
$("#exportExcel").on("click", function () {
  table.button(".buttons-excel").trigger();
});

function getTableSpecificOptions() {
  return {
    ordering: document.getElementById("gelirGiderTable") ? false : true,
  };
}
