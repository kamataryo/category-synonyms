# click to make input
jQuery ($)->
    $('.click2input, .click2inputs').on 'click', ->
        $(this)
            .hide()
            .next()
            .show()
            .focus()
            .select()


    $('.clicked2input').on 'focusout', ->
        $(this)
            .hide()
            .prev()
            .text $(this).val()
            .show()


    $('.clicked2inputs').on 'focusout', ->
        terms = $(this).val().split(',')
        $(this)
            .prev()
            .children()
            .remove()
        for term in terms
            if term isnt ''
                $(this).prev().append '<li>' + term + '</li>'
        $(this)
            .hide()
            .prev()
            .show()

    $('.click2add.page-title-action').on 'click', ->
        alert ajax.Endpoints
        #ここでajaxでsynonym-definitionを作成。
        #戻ってきた要素を入れる
        $('table.category-synonyms-ui>tbody.the-list').append trAJAXed
