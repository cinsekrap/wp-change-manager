{{-- Tags --}}
<div class="bg-white rounded-lg shadow p-4" id="tagsSection">
    <h2 class="text-sm font-semibold text-gray-900 mb-2">Tags</h2>
    <div id="tagsList" class="flex flex-wrap gap-1.5 mb-3">
        @foreach($changeRequest->tags as $tag)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium text-white tag-pill" style="background-color: {{ $tag->colour }}" data-tag-id="{{ $tag->id }}">
            {{ $tag->name }}
            <button type="button" onclick="removeTag({{ $changeRequest->id }}, {{ $tag->id }}, this)" class="ml-1 hover:text-gray-200 focus:outline-none">&times;</button>
        </span>
        @endforeach
    </div>
    <div class="relative">
        <input type="text" id="tagInput" placeholder="Add a tag..." autocomplete="off"
            class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        <div id="tagSuggestions" class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg py-1 max-h-40 overflow-y-auto"></div>
    </div>
</div>
<script>
(function() {
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
    var crId = {{ $changeRequest->id }};
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var tagInput = document.getElementById('tagInput');
    var tagSuggestions = document.getElementById('tagSuggestions');
    var tagsList = document.getElementById('tagsList');
    var allTags = @json(\App\Models\Tag::orderBy('name')->get(['id','name','colour']));

    function getCurrentTagIds() {
        var pills = tagsList.querySelectorAll('.tag-pill');
        var ids = [];
        pills.forEach(function(p) { ids.push(parseInt(p.dataset.tagId)); });
        return ids;
    }

    tagInput.addEventListener('input', function() {
        var val = this.value.trim().toLowerCase();
        if (!val) { tagSuggestions.classList.add('hidden'); return; }
        var currentIds = getCurrentTagIds();
        var matches = allTags.filter(function(t) {
            return t.name.toLowerCase().indexOf(val) !== -1 && currentIds.indexOf(t.id) === -1;
        });
        var html = '';
        matches.forEach(function(t) {
            html += '<button type="button" class="flex items-center w-full px-3 py-1.5 text-sm text-left hover:bg-gray-50" onclick="selectTag(\'' + escHtml(t.name).replace(/'/g, "&#39;") + '\')">' +
                '<span class="w-3 h-3 rounded-full mr-2 flex-shrink-0" style="background-color:' + escHtml(t.colour) + '"></span>' + escHtml(t.name) + '</button>';
        });
        if (!matches.length && val.length > 0) {
            html = '<button type="button" class="flex items-center w-full px-3 py-1.5 text-sm text-left hover:bg-gray-50 text-gray-500" onclick="selectTag(\'' + escHtml(val).replace(/'/g, "&#39;") + '\')">Create &ldquo;' + escHtml(val) + '&rdquo;</button>';
        }
        tagSuggestions.innerHTML = html;
        tagSuggestions.classList.remove('hidden');
    });

    tagInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var val = this.value.trim();
            if (val) selectTag(val);
        }
    });

    document.addEventListener('click', function(e) {
        if (!document.getElementById('tagsSection').contains(e.target)) {
            tagSuggestions.classList.add('hidden');
        }
    });

    window.selectTag = function(name) {
        tagInput.value = '';
        tagSuggestions.classList.add('hidden');
        fetch('/admin/requests/' + crId + '/tags', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ tag_name: name })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                var tag = data.tag;
                // Add to allTags if new
                if (!allTags.find(function(t) { return t.id === tag.id; })) {
                    allTags.push({ id: tag.id, name: tag.name, colour: tag.colour });
                }
                // Add pill if not already there
                if (!tagsList.querySelector('[data-tag-id="' + tag.id + '"]')) {
                    var span = document.createElement('span');
                    span.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium text-white tag-pill';
                    span.style.backgroundColor = tag.colour;
                    span.dataset.tagId = tag.id;
                    span.innerHTML = escHtml(tag.name) + ' <button type="button" onclick="removeTag(' + crId + ',' + tag.id + ',this)" class="ml-1 hover:text-gray-200 focus:outline-none">&times;</button>';
                    tagsList.appendChild(span);
                }
            }
        });
    };

    window.removeTag = function(crId, tagId, btn) {
        fetch('/admin/requests/' + crId + '/tags/' + tagId, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                btn.closest('.tag-pill').remove();
            }
        });
    };
})();
</script>
