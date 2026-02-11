

document.addEventListener('DOMContentLoaded', function () {

    
    
    
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 5000);
    });

    
    
    
    document.querySelectorAll('.btn-delete-confirm').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    
    
    
    document.querySelectorAll('.btn-copy-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-url') || '';
            if (url && url.charAt(0) === '/' && window.location && window.location.origin) {
                url = window.location.origin + url;
            }
            if (!url) return;
            var done = function () {
                var original = btn.getAttribute('data-copy-label') || 'Copy Link';
                btn.textContent = 'Copied!';
                setTimeout(function () { btn.textContent = original; }, 1500);
            };
            var fallback = function () {
                var temp = document.createElement('textarea');
                temp.value = url;
                temp.setAttribute('readonly', '');
                temp.style.position = 'fixed';
                temp.style.top = '-1000px';
                temp.style.left = '-1000px';
                document.body.appendChild(temp);
                temp.focus();
                temp.select();
                var ok = false;
                try {
                    ok = document.execCommand('copy');
                } catch (e) {
                    ok = false;
                }
                document.body.removeChild(temp);
                if (ok) {
                    done();
                } else {
                    window.prompt('Copy this link:', url);
                }
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done).catch(fallback);
            } else {
                fallback();
            }
        });
    });

    
    
    
    var questionContainer = document.getElementById('questions-container');
    var addQuestionBtn = document.getElementById('add-question-btn');

    if (addQuestionBtn && questionContainer) {
        var questionIndex = questionContainer.querySelectorAll('.question-block').length;

        addQuestionBtn.addEventListener('click', function () {
            addQuestionBlock(questionIndex);
            questionIndex++;
        });

        
        questionContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-question-btn')) {
                e.target.closest('.question-block').remove();
                renumberQuestions();
            }
        });

        
        questionContainer.addEventListener('change', function (e) {
            if (e.target.classList.contains('question-type-select')) {
                var block = e.target.closest('.question-block');
                toggleOptionsField(block, e.target.value);
            }
        });
    }

    function addQuestionBlock(idx) {
        var div = document.createElement('div');
        div.className = 'question-block card';
        div.innerHTML =
            '<div class="card-header"><h3>Question #' + (idx + 1) + '</h3>' +
            '<button type="button" class="btn btn-sm btn-danger remove-question-btn">Remove</button></div>' +
            '<div class="form-group">' +
            '<label>Type</label>' +
            '<select name="questions[' + idx + '][type]" class="question-type-select">' +
            '<option value="mcq">Multiple Choice</option>' +
            '<option value="tf">True/False</option>' +
            '<option value="short">Short Answer</option>' +
            '</select></div>' +
            '<div class="form-group"><label>Question Text</label>' +
            '<textarea name="questions[' + idx + '][question_text]" required></textarea></div>' +
            '<div class="form-group options-field">' +
            '<label>Options (one per line, for MCQ)</label>' +
            '<textarea name="questions[' + idx + '][options]" placeholder="Option A&#10;Option B&#10;Option C&#10;Option D"></textarea></div>' +
            '<div class="form-group"><label>Correct Answer</label>' +
            '<input type="text" name="questions[' + idx + '][correct_answer]" required></div>' +
            '<div class="form-group"><label>Points</label>' +
            '<input type="number" name="questions[' + idx + '][points]" value="1" min="0.5" step="0.5"></div>';
        questionContainer.appendChild(div);
    }

    function toggleOptionsField(block, type) {
        var optionsField = block.querySelector('.options-field');
        if (optionsField) {
            optionsField.style.display = (type === 'mcq') ? 'block' : 'none';
        }
    }

    function renumberQuestions() {
        var blocks = questionContainer.querySelectorAll('.question-block');
        blocks.forEach(function (block, i) {
            var h3 = block.querySelector('h3');
            if (h3) h3.textContent = 'Question #' + (i + 1);
            
            block.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/questions\[\d+\]/, 'questions[' + i + ']');
            });
        });
    }

    
    document.querySelectorAll('.question-block').forEach(function (block) {
        var select = block.querySelector('.question-type-select');
        if (select) toggleOptionsField(block, select.value);
    });

    
    
    
    var nodeContainer = document.getElementById('nodes-container');
    var addNodeBtn = document.getElementById('add-node-btn');

    if (addNodeBtn && nodeContainer) {
        var nodeIndex = nodeContainer.querySelectorAll('.node-block').length;

        addNodeBtn.addEventListener('click', function () {
            addNodeBlock(nodeIndex);
            nodeIndex++;
        });

        nodeContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-node-btn')) {
                e.target.closest('.node-block').remove();
            }
            if (e.target.classList.contains('add-choice-btn')) {
                var nodeBlock = e.target.closest('.node-block');
                var choicesList = nodeBlock.querySelector('.choices-list');
                var nIdx = nodeBlock.dataset.nodeIndex;
                var cIdx = choicesList.querySelectorAll('.choice-entry').length;
                addChoiceEntry(choicesList, nIdx, cIdx);
            }
            if (e.target.classList.contains('remove-choice-btn')) {
                e.target.closest('.choice-entry').remove();
            }
        });
    }

    function addNodeBlock(idx) {
        var div = document.createElement('div');
        div.className = 'node-block card';
        div.dataset.nodeIndex = idx;
        div.innerHTML =
            '<div class="card-header"><h3>Node #' + (idx + 1) + '</h3>' +
            '<button type="button" class="btn btn-sm btn-danger remove-node-btn">Remove Node</button></div>' +
            '<div class="form-group"><label>Node Key (unique ID)</label>' +
            '<input type="text" name="nodes[' + idx + '][node_key]" required placeholder="e.g. room_1"></div>' +
            '<div class="form-group"><label>Title</label>' +
            '<input type="text" name="nodes[' + idx + '][title]" required></div>' +
            '<div class="form-group"><label>Description</label>' +
            '<textarea name="nodes[' + idx + '][description]"></textarea></div>' +
            '<div class="form-check"><input type="checkbox" name="nodes[' + idx + '][is_end_node]" value="1">' +
            '<label>End Node (final node)</label></div>' +
            '<h4>Choices</h4>' +
            '<div class="choices-list"></div>' +
            '<button type="button" class="btn btn-sm btn-outline add-choice-btn mt-1">+ Add Choice</button>';
        nodeContainer.appendChild(div);
    }

    function addChoiceEntry(container, nodeIdx, choiceIdx) {
        var div = document.createElement('div');
        div.className = 'choice-entry d-flex gap-1 align-center mb-1';
        div.innerHTML =
            '<input type="text" name="nodes[' + nodeIdx + '][choices][' + choiceIdx + '][choice_text]" placeholder="Choice text" style="flex:2">' +
            '<input type="text" name="nodes[' + nodeIdx + '][choices][' + choiceIdx + '][target_node_key]" placeholder="Target node key" style="flex:1">' +
            '<button type="button" class="btn btn-sm btn-danger remove-choice-btn">X</button>';
        container.appendChild(div);
    }

    
    
    
    var sebForm = document.getElementById('seb-config-form');
    if (sebForm) {
        var urlFilterCheck = document.getElementById('seb_url_filter_enable');
        var urlFilterSection = document.getElementById('seb-url-filter-section');
        if (urlFilterCheck && urlFilterSection) {
            urlFilterCheck.addEventListener('change', function () {
                urlFilterSection.style.display = this.checked ? 'block' : 'none';
            });
            urlFilterSection.style.display = urlFilterCheck.checked ? 'block' : 'none';
        }
    }

    
    
    
    var timerEl = document.getElementById('exam-timer');
    if (timerEl) {
        var remaining = parseInt(timerEl.dataset.remaining, 10); 
        var timerForm = document.getElementById('exam-form');
        var timerInterval = setInterval(function () {
            remaining--;
            if (remaining <= 0) {
                clearInterval(timerInterval);
                if (timerForm) timerForm.submit();
                return;
            }
            var mins = Math.floor(remaining / 60);
            var secs = remaining % 60;
            timerEl.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
            if (remaining <= 60) {
                timerEl.style.color = 'var(--color-danger)';
                timerEl.style.fontWeight = '700';
            }
        }, 1000);
    }

    
    
    
    var activitySelect = document.getElementById('stats-activity-type');
    var examFilter = document.getElementById('stats-exam-filter');
    var gameFilter = document.getElementById('stats-game-filter');
    function syncStatsFilters() {
        if (!activitySelect) return;
        var val = activitySelect.value;
        if (examFilter) examFilter.style.display = (val === 'exam' || val === 'all') ? 'block' : 'none';
        if (gameFilter) gameFilter.style.display = (val === 'game' || val === 'all') ? 'block' : 'none';
    }
    if (activitySelect) {
        activitySelect.addEventListener('change', syncStatsFilters);
        syncStatsFilters();
    }

});





