const { result } = require('lodash');

require('./bootstrap');

const submit = document.getElementById('search-submit')
const resultContainer = document.getElementById('result-container')
const loadingContainer = document.getElementById('loading-container')
const resultContainerCollapseElement = document.getElementById('result-container-collapse')
const resultContainerCollapse = new bootstrap.Collapse(resultContainerCollapseElement, {
    toggle: false
})
resultContainerCollapseElement.addEventListener('hidden.bs.collapse', () => {
    if (resultContainer.classList.contains('hidden')) {
        resultContainer.classList.remove('hidden')
    }
    resetResults()
    resultContainerCollapse.show()
})

resultContainerCollapseElement.addEventListener('shown.bs.collapse', () => {
    fetch(`/api/crawl/${resultContainerCollapse.depth}/${resultContainerCollapse.url}`)
        .then(response => {
            return response.json()
        })
        .then(data => {

            if (data.hasOwnProperty('status') && data.status == false) {
                document.getElementById('loading-container').innerHTML = `<h2 class='text-danger'>ERROR: ${data.message}</h2>`
            } else {

                loadingContainer.classList.add('invisible')
                document.getElementById('scan-title').innerHTML = data[0].pageUrl
                document.getElementById('scan-page-count').innerHTML = data.length
                document.getElementById('scan-image-count').innerHTML = mergeResultSet(data, 'images').length
                document.getElementById('scan-internal-link-count').innerHTML = mergeResultSet(data, 'internalUrls').length
                document.getElementById('scan-external-link-count').innerHTML = mergeResultSet(data, 'externalUrls').length
                document.getElementById('scan-page-time').innerHTML = Math.round(averageResultSet(data, 'loadTime'))
                document.getElementById('scan-word-count').innerHTML = Math.round(averageResultSet(data, 'wordCount'))
                document.getElementById('scan-title-length').innerHTML = Math.floor((data.map(item => item['pageTitle'].length).reduce((sum, item) => sum += item) / data.length))
                document.getElementById('scan-table-body').innerHTML = data.map((item) => { return `<tr><td>${item['pageUrl']}</td><td>${statusToBadge(item['httpResponse'])}</td></tr>` }).join(' ')
            }
        })
})

statusToBadge = (status) => {
    console.log(status)
    statusType = (status % 100)
    if (statusType == '2') {
        return `<span class="badge bg-success">${status}</span>`
    } else if (statusType == '4') {
        return `<span class="badge bg-warning">${status}</span>`
    } else if (statusType == '5') {
        return `<span class="badge bg-danger">${status}</span>`
    } else {
        return `<span class="badge bg-secondary">${status}</span>`
    }
}

loadCrawl = (depth, url) => {
    resultContainerCollapse.depth = depth
    resultContainerCollapse.url = url
    resultContainerCollapse.hide()
}

averageResultSet = (obj, property) => {
    let count = 0
    let iter = 0
    obj.forEach((item) => {
        count += parseFloat(item[property])
        iter++
    })
    return count / iter
}

mergeResultSet = (obj, property) => {
    unique = (value, index, self) => {
        return self.indexOf(value) === index
    }
    let result = []
    obj.forEach((item) => {
        if (Array.isArray(item[property])) {
        result = result.concat(item[property])
        } else {

        result = result.concat(Object.entries(item[property]))
        }
    })
    console.log(result)
    const out = result.filter(unique)
    console.log(out)
    return out
}

resetResults = () => {
    loadingContainer.classList.remove('invisible')
    document.getElementById('loading-container').innerHTML = '<div class="spinner-border"></div><h5>Loading...</h5>'
    document.getElementById('scan-title').innerHTML = ''
    document.getElementById('scan-page-count').innerHTML = '<span class="placeholder col-2"></span>'
    document.getElementById('scan-image-count').innerHTML = '<span class="placeholder col-2"></span>'
    document.getElementById('scan-internal-link-count').innerHTML = '<span class="placeholder col-2"></span>'
    document.getElementById('scan-external-link-count').innerHTML = '<span class="placeholder col-2"></span>'
    document.getElementById('scan-page-time').innerHTML = '<span class="placeholder col-2"></span>'
    document.getElementById('scan-word-count').innerHTML = '<span class="placeholder col-2"></span>'
    document.getElementById('scan-title-length').innerHTML = '<span class="placeholder col-2"></span>'
    document.getElementById('scan-table-body').innerHTML = '<tr><td><span class="placeholder col-6"></span></td><td><span class="placeholder col-2"></span></td></tr>'
}

window.onload = () => {
    const urlComponents = window.location.href.split('/')

    if (urlComponents.length > 4) {
        depth = urlComponents[3]
        urlComponents.splice(0, 4)
        url = urlComponents.join('/')
        // 2 argument are present in the URL
        loadCrawl(depth, url)
    }
}

submit.addEventListener('click', () => {
    const url = document.getElementById('search-url')
    const depth = document.getElementById('search-url-depth')
    window.history.pushState(`/${depth.value}/${url.value}`, '', `/${depth.value}/${url.value}`)
    loadCrawl(depth.value, url.value)
})