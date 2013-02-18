function Page() {
    this.isLoading = true;
    this.bodyTag   = document.getElementsByTagName('body')[0];

    this.addLoader = function() {
        if (this.isLoading) {
            return;
        }

        var container = document.createElement('div'),
            loader    = document.createElement('img');

        loader.setAttribute('src', '/style/ajax-loader.gif');
        loader.setAttribute('alt', 'loading...');

        container.className = 'loader';
        container.appendChild(loader);

        this.bodyTag.appendChild(container);
        this.bodyTag.className = 'loading';
    };

    this.removeLoader = function() {
        if (!this.isLoading) {
            return;
        }

        var loader = document.querySelector('.loader');

        loader.parentNode.removeChild(loader);

        this.bodyTag.className = '';
    };

    this.getTitle = function() {
        return document.title;
    };

    this.setTitle = function(title) {
        document.title = title;
    }
}