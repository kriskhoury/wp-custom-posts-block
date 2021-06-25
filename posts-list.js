( function() {
  new Vue({
    el: document.getElementById('app'),
    data: {
      apiEndPoint: '/wp-json/v1/posts',
      results: {
        posts: {},
      },
      pageNumber: 1,
      recordsToShow: 10,
      loading: true,
    },
    computed:{
      posts(){
        return this.results.posts;
      }
    },
    created(){
      this.getResults();
    },
    methods:{
      gotoPage(page){
        this.pageNumber = page;
        this.getResults();
      },
      prevClick(){
        if(this.pageNumber > 1){
          this.pageNumber--;
          this.getResults();
        }
      },
      nextClick(){
        if(this.pageNumber < this.posts.length){
          this.pageNumber++;
          this.getResults();
        }
      },
      getResults(){
        this.results.posts = [];
        this.loading = true;
        const self = this;
        const requestData = {
          'page': this.pageNumber,
          'per_page': this.recordsToShow
        }
        const requestOptions = {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(requestData)
        };
        fetch(this.apiEndPoint, requestOptions)
          .then((r) => r.json())
          .then((res) => this.results = res)
          .then(() => {
            self.loading = false;
          })
      },
    }
  });
})();