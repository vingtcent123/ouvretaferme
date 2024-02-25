document.addEventListener('navigation.sleep', () => {
	Editor.endAll();
});

class Editor {

	static labels = {};
	static conf = {};

	static sleeping = true;

	static instances = [];

	static wakeup() {

		if(this.sleeping === false) {
			return;
		}

		this.sleeping = false;

		this.selection();

	};

	// Start handling off contenteditable
	static startAll() {

		qsa('.editor', editor => {

			if(editor.id) {
				this.startInstance('#'+ editor.id);
			} else {
				throw 'Id is missing for .editor';
			}


		});

	};

	// Remove all registered contenteditable
	static endAll() {

		this.instances.forEach(instanceId => this.endInstance(instanceId));

	};

	// Start handling a contenteditable div
	static startInstance(instanceId) {

		this.wakeup();

		const instance = qs(instanceId);

		if(instance.getAttribute('data-started') === '1') {
			return;
		}

		this.instances.push(instanceId); // Save instanceId
		EditorMutation.observe(instanceId); // Save mutation observer

		instance.setAttribute('data-started', '1');

		EditorFormat.start(instanceId);

		EditorFigure.reorganizeInstance(instanceId);

	};

	// End handling a contenteditable div
	static endInstance(instanceId) {

		const position = this.instances.indexOf(instanceId);

		if(position === -1) {
			return;
		}

		const instance = document.getElementById(instanceId);

		// Instance exists but is not started yet
		if(
			instance !== null &&
			instance.getAttribute('data-started') !== '1'
		) {
			return;
		}

		this.resetInstance(instanceId);

		// Delete instanceId
		delete this.instances[position];

		// Disconnect and delete mutation observer
		EditorMutation.disconnect(instanceId);

		EditorFormat.end(instanceId);

		if(instance !== null) {
			instance.innerHTML ='';
			instance.setAttribute('data-started', '0');
		}

	};

	// Reset all instances
	static resetAll() {

		this.instances.forEach(instanceId => this.resetInstance(instanceId));

	};

	// Reset instance
	static resetInstance(instanceId) {

		EditorFigure.deselect(instanceId);
		EditorFormat.reset(instanceId);

	};

	// Monitor selected text for active contenteditable divs
	static selection() {

		let lastRange = null;
		let lastInstanceId = null;

		let counter = 0; // +1 = +100ms

		setInterval(() => {

			const currentRange = EditorRange.get();

			Object.entries(this.instances).forEach(([key, instanceId]) => {

				const instance = document.getElementById(instanceId.substring(1));

				if(instance === null) {
					this.endInstance(instanceId);
					return;
				}

				// Element is focused
				if(
					(instance.matches(":focus") && qs(instanceId +' figure:not(:focus)')) ||
					qsa(instanceId +' [contenteditable="true"]:focus').length > 0 ||
					EditorRange.isIn(currentRange, instanceId)
				) {

					EditorRange.saveLast(currentRange);

					// No range
					if(currentRange === null) {

						if(lastRange !== null) {

							this.selectionCollapsed(instanceId);

							lastRange = null;
							lastInstanceId = null;

						}

					}
					// Simple cursor
					else if(currentRange.collapsed) {

						if(lastRange !== null) {

							this.selectionCollapsed(instanceId);

							lastRange = null;
							lastInstanceId = null;

						}

						const container = currentRange.startContainer.nodeType === Node.TEXT_NODE ? currentRange.startContainer.parentElement : currentRange.startContainer;
						const containerFigure = this.getFirstAncestor(container, 'FIGURE');

						// Firefox hack pour MAC
						// Il replace parfois le curseur au tout début de l'éditeur quand on fait une action sur une figure
						const isContainerEditor = (
							currentRange.startContainer === instance &&
							currentRange.startOffset === 0 &&
							currentRange.endContainer=== instance &&
							currentRange.endOffset === 0
						);
						if(
							isContainerEditor === false && (
								containerFigure === null ||
								containerFigure.getAttribute('data-interactive') === 'false'
							)
						) {

							EditorFigure.deselect(instanceId);

						}

						if(container.tagName === 'FIGCAPTION') {
							EditorFigure.selectCaption(instanceId);
						} else {
							EditorFigure.deselectCaption(instanceId);
						}

					}
					// Selection
					else {

						const container = currentRange.startContainer.nodeType === Node.TEXT_NODE ? currentRange.startContainer.parentElement : currentRange.startContainer;
						const containerFigure = this.getFirstAncestor(container, 'FIGURE');

						if(
							containerFigure === null ||
							containerFigure.getAttribute('data-interactive') === 'false'
						) {

							if(
								lastRange === null ||
								lastRange.startContainer !== currentRange.startContainer ||
								lastRange.startOffset !== currentRange.startOffset ||
								lastRange.endContainer !== currentRange.endContainer ||
								lastRange.endOffset !== currentRange.endOffset
							) {

								// User is manipulating the selection
								if(isMouseDown) {

								} else {

									this.selectionExpanded(instanceId, currentRange);

									lastRange = currentRange;
									lastInstanceId = instanceId;

								}

							}

							EditorFigure.deselect(instanceId);

						}

						if(container.tagName === 'FIGCAPTION') {
							EditorFigure.selectCaption(instanceId);
						} else {
							EditorFigure.deselectCaption(instanceId);
						}

					}

				} else {

					if(
						lastInstanceId === instanceId &&
						qs(lastInstanceId +'-box-selection').isVisible() &&
						qs(lastInstanceId +'-box-selection:hover') === null &&
						qs(lastInstanceId +'-box-selection input:focus') === null
					) {

						this.selectionCollapsed(lastInstanceId);

						lastRange = null;
						lastInstanceId = null;

					}

				}

				if(EditorRange.isIn(currentRange, instanceId)) {
					EditorFormat.updateLine(instanceId, currentRange);
				}

				qsa(instanceId +' figcaption', figcaptionSelector => {

					if(figcaptionSelector.matches(':focus')) {

						if(figcaptionSelector.innerHTML === '') {
							figcaptionSelector.innerHTML = '&nbsp;';
						}

					} else {

						if(figcaptionSelector.innerHTML === '&nbsp;') {
							figcaptionSelector.innerHTML = '';
						}

					}

				});

				// Fixed #1138 (workaround, we have to check regularly if it is fixed)
				if(browser.isSafari) {

					const instance = document.getElementById(instanceId.substring(1));

					for(let i = 0; i < instance.childNodes.length; i++) {

						const node = instance.childNodes[i];

						// Garbage collector for nasty browsers
						// Destroy empty lists and p
						if(
							(node.tagName === 'OL' || node.tagName === 'UL' || node.tagName === 'P') &&
							node.innerHTML === ''
						) {
							node.parentElement.removeChild(node);
							i--;
							continue;
						}

					}

				}

			});

			counter++;

		}, 100);

	};

	// returns first ancestor that matchs the given tag
	static getFirstAncestor(node, tag) {

		let nodeCheck = node;

		while(nodeCheck.parentElement) {

			if(this.isEditorNode(nodeCheck)) {
				return null;
			}

			if(nodeCheck.tagName === tag) {
				return nodeCheck;
			}

			nodeCheck = nodeCheck.parentElement;

		}

		return null;

	};

	// returns first ancestor that matchs the given tag
	static isBaseNode(node) {
		return ['P', 'UL', 'OL', 'FIGURE'].includes(node.tagName);
	}

	// returns first ancestor that matchs the given tag
	static getBaseAncestor(node) {

		let nodeCheck = node;

		while(nodeCheck.parentElement) {

			if(this.isEditorNode(nodeCheck)) {
				return null;
			}

			if(['P', 'UL', 'OL', 'FIGURE'].includes(nodeCheck.tagName)) {
				return nodeCheck;
			}

			nodeCheck = nodeCheck.parentElement;

		}

		return null;

	};

	// returns contenteditable node
	static getEditorNode(node) {

		let nodeCheck = node;

		while(nodeCheck) {

			if(this.isEditorNode(nodeCheck)) {
				return nodeCheck;
			}

			nodeCheck = nodeCheck.parentElement;
		}

		return null;

	};

	static isEditorNode(node) {

		return (
			node &&
			node.nodeType === Node.ELEMENT_NODE &&
			node.hasAttribute('data-editor')
		);

	};

	static isNodeIn(node, tagCheck) {

		let nodeCheck = node;

		while(
			nodeCheck &&
			this.isEditorNode(nodeCheck) === false
		) {

			if(
				nodeCheck.nodeType === Node.ELEMENT_NODE &&
				nodeCheck.tagName === tagCheck
			) {
				return true;
			}

			nodeCheck = nodeCheck.parentElement;
		}

		return false;

	};

	// Display a formatting box for the selected text
	static selectionExpanded(instanceId, current) {

		EditorFormat.displaySelection(instanceId, current);

	};

	// No more selected text
	static selectionCollapsed(instanceId) {

		EditorFormat.hideSelection(instanceId);

	};

};

// Handle keyboards events, such as <hr>, lists...
document.delegateEventListener('keydown', '.editor', function(e) {

	const instanceId = '#'+ this.id;

	const selection = window.getSelection();

	// Backspace
	switch(e.which) {

		case 8 : // Backspace

			// Contenteditable is focused but has no selection
			if(selection.rangeCount === 0) {
				e.preventDefault(); // Prevent history back
				return;
			}

			if(EditorKeyboard.backspace(instanceId, e, selection)) {
				e.preventDefault();
			}

			break;

		case 46 : // Del

			if(selection.rangeCount === 0) {

				e.preventDefault();

				// Drop the selected figure
				const figureSelector = EditorFigure.getSelected(this);

				if(figureSelector !== null) {
					EditorFigure.remove(instanceId, figureSelector);
				}

				return;

			}

			if(EditorKeyboard.del(instanceId, e, selection)) {
				e.preventDefault();
			}

			break;

		case 13 : // Enter

			if(selection.rangeCount === 0) {

				// Add a new line before the selected figure
				const figureSelector = EditorFigure.getSelected(this);

				if(figureSelector !== null) {
					e.preventDefault();
					EditorFigure.insertLineBefore(instanceId, figureSelector);
				}

			}

			break;

		case 69 : // e
		case 66 : // b
		case 85 : // u
		case 73 : // i
		case 75 : // k

			if(selection.rangeCount > 0) {

				const range = selection.getRangeAt(0);

				EditorKeyboard.shortcuts(instanceId, range, e, String.fromCharCode(e.which).toLowerCase());

			}

			break;

	}


});

document.delegateEventListener('keypress', '.editor', function(e) {

	const instanceId = '#'+ this.id;

	const selection = window.getSelection();

	if(selection.rangeCount > 0) {

		const range = selection.getRangeAt(0);
		const character = String.fromCharCode(e.charCode);

		EditorKeyboard.shortcuts(instanceId, range, e, character);

	}

});

document.delegateEventListener('keyup', '.editor', function(e) {

	const instanceId = '#'+ this.id;
	const selection = window.getSelection();

	// No focus
	if(selection.rangeCount === 0) {
		return;
	}

	const range = selection.getRangeAt(0);

	// New line
	if(e.which === 13) {

		if(selection.rangeCount > 0) {

			// Remove header if exists
			const nodeP = Editor.getFirstAncestor(range.startContainer, 'P');

			if(nodeP !== null && nodeP.hasAttribute('data-header')) {
				nodeP.removeAttribute('data-header');
			}

		}

		const node = EditorKeyboard.getTextNodeFromPreviousLine(range.commonAncestorContainer);

		if(node !== null) {
			EditorFormat.action('embed', instanceId, node);
		}

	}

	EditorFormat.updateLine(instanceId, range);

});

class EditorKeyboard {

	static smileys = {
		':)' : '😀', ':-)' : '😀',
		':(' : '😦', ':-(' : '😦',
		';)' : '😉',
		';-)' : '😉',
		':\'(' : '😥',
		':p' : '😛',	':-p' : '😛',
		'(sleep)' : '😴',
		'(angry)' : '😡',
		'(a)' : '😇',
		'(yes)' : '👍',
		'(no)' : '👎',
		'(l)' : '💗',
		'(k)' : '😘'
	};

	static backspace(instanceId, e, selection) {

		const node = EditorRange.lineStart(selection);

		if(node === null) {
			return false;
		}

		// Within a figure, backspaces are not allowed at the beginning of a figcaption
		if(node.tagName === 'FIGCAPTION') {
			return true;
		}

		// In the editor
		const previousNode = EditorNode.siblingNoWhitespaces(node, 'previousSibling');

		if(previousNode === null) {
			return true;
		} else {

			switch(previousNode.tagName) {

				case 'FIGURE' :

					const topNode = Editor.getBaseAncestor(selection.getRangeAt(0).startContainer);

					if(EditorKeyboard.isEmptyLine(topNode)) {
						topNode.remove();
					}

					EditorFigure.select(instanceId, previousNode);

					return true;

				case 'P' :

					if(browser.isFirefox) {

						// Firefox does not know how to remove a line
						if(EditorKeyboard.isEmptyLine(node)) {

							node.parentElement.removeChild(node);
							EditorRange.replaceEnd(previousNode);

							return true;

						} else {

							if(EditorKeyboard.isEmptyLine(previousNode)) {

								previousNode.parentElement.removeChild(previousNode);

								return true;

							} else {

								EditorMutation.disconnect(instanceId);

								EditorRange.replaceEnd(previousNode);

								while(node.childNodes.length > 0) {
									previousNode.appendChild(node.firstChild);
								}


								EditorMutation.observe(instanceId);

								return true;

							}

						}

					}

					break;

				case 'OL' :
				case 'UL' :

					// Firefox does not know how to remove a line
					if(browser.isFirefox) {

						const previousLi = previousNode.querySelector('li:last-child');

						if(EditorKeyboard.isEmptyLine(node)) {

							node.remove();

							EditorRange.replaceEnd(previousLi); // End of last LI

							return true;

						} else {

							EditorMutation.disconnect(instanceId);

							EditorRange.replaceEnd(previousLi);

							while(node.childNodes.length > 0) {
								previousLi.appendChild(node.firstChild);
							}


							EditorMutation.observe(instanceId);

							return true;

						}

					}

					break;

			}

		}

		return false;


	};

	static del(instanceId, e, selection) {

		const node = EditorRange.lineEnd(selection);

		if(node === null) {
			return false;
		}

		// Within a figcaption
		if(node.tagName === 'FIGCAPTION') {
			return true;
		}

		// In the editor
		const nextNode = EditorNode.siblingNoWhitespaces(node, 'nextSibling');

		if(nextNode === null) {
			return true;
		} else {

			switch(nextNode.tagName) {

				case 'FIGURE' :

					if(EditorKeyboard.isEmptyLine(node)) {
						EditorRange.saveState();
						EditorNode.removeRecursive(node);
					}

					EditorFigure.select(instanceId, nextNode);

					return true;

				case 'P' :

					if(browser.isFirefox) {

						if(EditorKeyboard.isEmptyLine(node)) {

							node.parentElement.removeChild(node);
							EditorRange.replace(nextNode, 0);

							return true;

						}

					}

					break;



			}

		}

		return false;


	};

	static shortcuts(instanceId, range, e, character) {

		const container = range.commonAncestorContainer;

		switch(character) {

			case 'b' :
			case 'i' :
			case 'u' :

				if(e.ctrlKey || e.metaKey) {

					const actions = {
						b: 'bold',
						i: 'italic',
						u: 'underline'
					};

					e.preventDefault();

					EditorFormat.action(actions[character], instanceId);


				}

				break;


			case 'k' :

				if(
					range.collapsed === false &&
					(e.ctrlKey || e.metaKey)
				) {

					e.preventDefault();

					EditorFormat.action('link-open', instanceId);

				}

				break;

			case ' ' : // space
			case String.fromCharCode(160) : // space

				const nodeUl = EditorKeyboard.checkLine(container, '-') || EditorKeyboard.checkLine(container, '*');

				if(nodeUl !== null) {
					e.preventDefault();
					return EditorKeyboard.list(nodeUl, 'ul');
				}


				const nodeLi = EditorKeyboard.checkLine(container, '#') || EditorKeyboard.checkLine(container, '1.');

				if(nodeLi !== null) {
					e.preventDefault();
					return EditorKeyboard.list(nodeLi, 'ol');
				}

				break;

			case '(' :
			case ')' :
			case 'p' :

				if(EditorKeyboard.smiley(container, e)) {
					return true;
				}

				break;
		}

	};

	static smiley(nodeExisting, e) {

		// Look for text pattern
		if(nodeExisting.nodeType !== Node.TEXT_NODE) {
			return false;
		}

		const range = window.getSelection().getRangeAt(0);

		if(
			range.startContainer === nodeExisting &&
			range.collapsed
		) {

			return EditorKeyboard._replaceSmileys(nodeExisting, e, range);

		}

		return false;

	};

	/**
	 * Changes the smileys into <img> tag
	 */
	static _replaceSmileys(node, e, range) {

		const message = node.nodeValue;
		const lastCharacter = String.fromCharCode(e.charCode);

		for(let shortcut in EditorKeyboard.smileys) {

			if(message.substring(range.startOffset - shortcut.length + 1, range.startOffset) + lastCharacter === shortcut) {

				e.preventDefault();

				const startMessage = message.substring(0, range.startOffset - shortcut.length + 1);
				const endMessage = message.substring(range.startOffset);

				const fragment = document.createDocumentFragment();

				if(startMessage) {
					fragment.appendChild(document.createTextNode(startMessage));
				}

				fragment.appendChild(document.createTextNode(EditorKeyboard.smileys[shortcut]));

				let nodeEnd;
				let offsetEnd;

				if(endMessage) {

					nodeEnd = document.createTextNode(endMessage);
					offsetEnd = 0;

					fragment.appendChild(nodeEnd);

				} else {

					nodeEnd = node.parentElement;
					offsetEnd = node.parentElement.childNodes.length + fragment.childNodes.length - 1;

					if(
						node.parentElement.lastChild.nodeType === Node.ELEMENT_NODE &&
						node.parentElement.lastChild.tagName === 'BR'
					) {
						offsetEnd--;
					}

				}

				node.parentElement.replaceChild(fragment, node);

				EditorRange.replace(nodeEnd, offsetEnd);

				return true;

			}


		}

		return false;

	};

	static list(nodeExisting, type) {

		const nodeUl = document.createElement(type);
		const nodeLi = document.createElement('li');
		nodeUl.appendChild(nodeLi);

		// Can't focus on <br> on firefox
		let nodeBr;
		if(browser.isFirefox) {
			nodeBr = document.createTextNode('\u00A0');
		} else {
			nodeBr = document.createElement('br');
		}

		nodeLi.appendChild(nodeBr);

		nodeExisting.parentElement.replaceChild(nodeUl, nodeExisting);

		EditorRange.replace(nodeBr, 0);

		return true;

	};

	// Checks if a node is inside an empty line
	static isEmptyLine(nodeCheck) {

		if(nodeCheck.nodeType === Node.TEXT_NODE) {
			return false;
		}

		if(Editor.isEditorNode(nodeCheck) && nodeCheck.innerHTML === '') {
			return true;
		}

		if(nodeCheck === null || nodeCheck.tagName !== 'P') {
			return false;
		}

		try {

			const run = function(node) {

				for(let i = 0; i < node.childNodes.length; i++) {

					const childNode = node.childNodes[i];

					if(
						childNode.nodeType === Node.TEXT_NODE ||
						childNode.tagName === 'IMG' || // <img>
						childNode.tagName === 'LI' // <li> in <ul>
					) {
						throw 'error';
					} else {
						run(childNode);
					}

				}

			};

			run(nodeCheck);

			return true;

		} catch(e) {
			return false;
		}

	};

	// Checks if a text node is alone on the previous line
	static getTextNodeFromPreviousLine(nodeCurrent) {

		if(EditorKeyboard.isEmptyLine(nodeCurrent) === false) {
			return null;
		}

		const nodeCurrentAncestor = Editor.getBaseAncestor(nodeCurrent);

		if(nodeCurrentAncestor.previousSibling === null) {
			return null;
		}

		const nodePrevious = nodeCurrentAncestor.previousSibling;

		try {

			let nodeText = null;

			nodePrevious.childNodes.forEach(nodeCheck => {

				if(
					nodeText === null &&
					nodeCheck.nodeType === Node.TEXT_NODE
				) {
					nodeText = nodeCheck;
					return;
				}

				if(
					nodeCheck.nodeType === Node.TEXT_NODE || // Omg a second text node
					nodeCheck.tagName === 'LI'
				) {
					throw 'error';
				}

			});

			return nodeText;

		} catch(e) {
			return null;
		}

	};

	// Checks if a node contains the given text and is alone on its line
	static checkLine(nodeCheck, text) {


		// Look for text pattern
		if(
			nodeCheck.nodeType !== Node.TEXT_NODE ||
			nodeCheck.nodeValue !== text
		) {
			return null;
		}

		// Nothing after the node
		if(
			nodeCheck.nextSibling !== null &&
			(!browser.isFirefox || nodeCheck.nextSibling.tagName !== 'BR') // Firefox hack
		) {
			return null;
		}

		// Check if we are at the very beginning of a line
		let node = nodeCheck;

		while(
			// Root node (contenteditable div)
			// Stop looping
			node &&
			Editor.isBaseNode(node) === false
		) {

			// Current node must be the first child of its parent node
			if(node.parentElement.firstChild !== node) {
				return null;
			}

			node = node.parentElement;

		}

		return node;

	};

}

// Display a formatting box for selected text
// Handle <b>, <u>, <i>...
document.delegateEventListener('click', '.editor-action, [data-action-run="1"]', function(e) {

	e.preventDefault();
	e.stopImmediatePropagation();

	const action = this.getAttribute('data-action');
	const instanceId = this.getAttribute('data-instance');

	EditorFormat.action(action, instanceId, this);

});

document.delegateEventListener('focusin', '.editor', function(e) {

	e.preventDefault();

	if(this.innerHTML === '') {

		const nodeP = EditorParagraph.getEmpty();

		this.appendChild(nodeP);
		EditorRange.replaceEnd(nodeP);

	}

});

document.delegateEventListener('focusout', '.editor', function(e) {

	e.preventDefault();

	if(
		this.getAttribute('data-figure') === '0' &&
		this.innerHTML.match(/^<p(?: [a-z\-]+="[a-zA-Z0-9]+")*>(?:<br>)*<\/p>$/) !== null
	) {
		this.innerHTML = '';
	}

});

// Prevent click on links in editor
document.delegateEventListener('click', '.editor p a', function(e) {

	e.preventDefault();

});

document.delegateEventListener('click', '.editor figure', function(e) {

	const instanceId = '#'+ Editor.getEditorNode(this).id;

	EditorFigure.select(instanceId, this);


});

// Display actions for medias
document.delegateEventListener('mouseenter', '.editor figure > div.editor-media', function(e) {

	e.preventDefault();

	if(EditorFormat.isMoving()) {
		return;
	}

	const instanceId = '#'+ Editor.getEditorNode(this).id;
	const figureId = '#' + this.parentElement.id;

	EditorFormat.displayMedia(instanceId, figureId, this);

});

document.delegateEventListener('mouseleave', '.editor figure > div.editor-media', function(e) {

	e.preventDefault();

	const instanceId = '#'+ Editor.getEditorNode(this).id;

	EditorFormat.hideMedia(instanceId);

});

// Allow to move medias
document.delegateEventListener('mouseenter', '.editor-action[data-action="media-move"]', function(e) {

	const instanceId = this.getAttribute('data-instance');

	EditorFormat.action('move-media-open', instanceId, this);

});

document.delegateEventListener('mouseleave', '.editor-action[data-action="media-move"]', function(e) {

	if(isMouseDown === false) {

		const instanceId = this.getAttribute('data-instance');

		EditorFormat.action('move-media-close', instanceId, this);


	}

});

// Allow to move figures
document.delegateEventListener('mouseenter', '.editor-action[data-action="figure-move"]', function(e) {

	const instanceId = this.getAttribute('data-instance');

	EditorFormat.action('move-figure-open', instanceId, this);

});

document.delegateEventListener('mouseleave', '.editor-action[data-action="figure-move"]', function(e) {

	if(isMouseDown === false) {

		const instanceId = this.getAttribute('data-instance');

		EditorFormat.action('move-figure-close', instanceId, this);


	}

});

// Handle image download
document.delegateEventListener('change', 'input.editor-upload', function(e) {

	e.preventDefault();
	e.stopPropagation();

	const instanceId = this.getAttribute('data-instance');
	const figureId = this.getAttribute('data-figure') || null;

	EditorImage.upload(instanceId, figureId, e, this.files);

	this.value = '';

});


document.delegateEventListener('submit', '#editor-link-form', function(e) {

	e.preventDefault();

	const instanceId = this.getAttribute('data-instance');

	EditorFormat.action('link', instanceId, this);

});

// Placeholder for figcaption (workaround)
document.delegateEventListener('input', '.editor figcaption', function(e) {

	if(this.innerHTML === '') {
		this.innerHTML = '&nbsp;';
	}

	this.focus();

});

document.delegateEventListener('submit', '#editor-media-configure', function(e) {

	e.preventDefault();

	const url = qs('#editor-media-configure input[name="url"]').value;

	qs('div[data-url="' + url + '"]', media => {
		media.setAttribute('data-title', qs('#editor-media-configure input[name="title"]').value);
		media.setAttribute('data-link', qs('#editor-media-configure input[name="link"]').value);
	});

	qs('#panel-editor-media-configure', node => Lime.Panel.close(node));

});

document.delegateEventListener('click', '[data-action="media-download"]', function(e) {

	e.preventDefault();

	const url = this.getAttribute('data-url');
	EditorImage.download(url);

});

document.delegateEventListener('click', '[data-action="media-rotate-show"]', function(e) {

	e.preventDefault();

	const rotate = qs('#media-rotate-list');

	if(rotate.style.display === 'none') {
		rotate.style.display = 'inline-block';
	} else {
		rotate.style.display = 'none';
	}

});

document.delegateEventListener('click', '[data-action="media-rotate"]', function(e) {

	e.preventDefault();

	qs('#media-rotate-list').classList.add('processing');
	qsa('#media-rotate-list a', node => node.removeAttribute('data-action'));

	this.childNodes.filter('img').forEach(node => node.classList.add('animate'));

	const hash = this.getAttribute('data-xyz');
	const angle = this.getAttribute('data-angle');
	const mediaSelector = qs('.editor-media[data-xyz="' + hash + '"]');

	new EditorMedia().rotate(mediaSelector, null, hash, angle);

});

document.delegateEventListener('click', '[data-action="media-separate"]', function(e) {

	e.preventDefault();

	const nodeHost = EditorParagraph.getEmpty();

	const instanceId = this.getAttribute('data-instance');
	const mediaSelector = EditorFormat.currentMedia[instanceId];

	const figureSelector = mediaSelector.parentElement;

	figureSelector.parentElement.insertBefore(nodeHost, figureSelector);

	const newFigureId = EditorFigure.create(instanceId, {
		nodeHost: nodeHost,
		interactive: true
	});

	EditorFigure.addSelector(instanceId, newFigureId, mediaSelector);

	EditorFormat.hideMedia(instanceId);

	figureSelector.removeAttribute('style');
	figureSelector.childNodes.filter('.editor-media').forEach(node => node.removeAttribute('style'));

	EditorFigure.reorganize(instanceId, figureSelector);

	qs('#panel-editor-media-configure', node => Lime.Panel.close(node));

});

// Videos
document.delegateEventListener('keyup', '#editor-video-value', function(e) {

	if(e.key === 'Escape') {

		const instanceId = qs('#panel-editor-video').getAttribute('data-instance');
		EditorFormat.action('video', instanceId, this);

	}

});

document.delegateEventListener('submit', '#editor-video-form', function(e) {

	e.preventDefault();

	const instanceId = qs('#panel-editor-video').getAttribute('data-instance');
	EditorFormat.action('video', instanceId, this);

});

document.delegateEventListener('submit', '#editor-grid-form', function(e) {

	e.preventDefault();

	const instanceId = qs('#panel-editor-grid').getAttribute('data-instance');
	EditorFormat.action('grid', instanceId, this);

});

// Quotes
document.delegateEventListener('click', '[data-action="quote-image"]', function(e) {

	e.preventDefault();

	EditorQuote.next(this);

});

class EditorFormat {

	static mediaMoving = false;

	static start(instanceId) {

		const instance = qs(instanceId);

		document.execCommand('enableObjectResizing', false, false);

		let html = '<div id="'+ instanceId.substring(1) +'-format">';

			html += '<input class="editor-upload" data-instance="'+ instanceId +'" type="file" multiple="multiple" accept="image/*">';

			html += '<div id="'+ instanceId.substring(1) +'-box-selection" class="editor-box-selection">'+
				'<div class="editor-box-selection-icons">'+
					'<button class="editor-action" data-action="bold" data-instance="'+ instanceId +'">'+ Lime.Asset.icon('type-bold') +'</button>'+
					'<button class="editor-action" data-action="italic" data-instance="'+ instanceId +'">'+ Lime.Asset.icon('type-italic') +'</button>'+
					'<button class="editor-action" data-action="underline" data-instance="'+ instanceId +'">'+ Lime.Asset.icon('type-underline') +'</button>'+
					'<button class="editor-action" data-action="link-open" data-instance="'+ instanceId +'" title="'+ Editor.labels.link +'">'+ Lime.Asset.icon('link') +'</button>';

			html += '<span class="separator">&nbsp;</span>';

			html += '<button class="editor-action" data-action="align-left" data-instance="'+ instanceId +'">'+ Lime.Asset.icon('text-left') +'</button>';
			html += '<button class="editor-action" data-action="align-center" data-instance="'+ instanceId +'">'+ Lime.Asset.icon('text-center') +'</button>';
			html += '<button class="editor-action" data-action="align-right" data-instance="'+ instanceId +'">'+ Lime.Asset.icon('text-right') +'</button>';
			html += '<button class="editor-action" data-action="align-justify" data-instance="'+ instanceId +'">'+ Lime.Asset.icon('justify') +'</button>';
			html += '<span class="separator" data-action="align-separator">&nbsp;</span>';

			html += '<button class="editor-action" data-action="header" data-instance="'+ instanceId +'" title="'+ Editor.labels.header +'"><span class="editor-header">T</span></button>';


			html += '</div>'+
				'<div class="editor-box-selection-link">'+
					'<form id="editor-link-form" data-instance="'+ instanceId +'">'+
						'<input type="text" placeholder="'+ Editor.labels.placeholderLink +'" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"/>'+
						'<button type="submit"></button>'+
						'<a class="editor-action" data-action="link-close" data-instance="'+ instanceId +'"></a>'+
					'</form>'+
				'</div>'+
			'</div>';

			if(instance.getAttribute('data-figure') === '1') {

				html += '<div id="'+ instanceId.substring(1) +'-box-line" class="editor-box-line" data-instance="'+ instanceId +'">'+
					'<div class="editor-box-line-open">'+
						'<button class="editor-action" data-action="line-swap" data-instance="'+ instanceId +'" title="'+ Editor.labels.media +'">'+ Lime.Asset.icon('plus') +'</button>'+
						'<div class="editor-box-line-helper" style="visibility: hidden;">'+ instance.getAttribute('data-placeholder-empty') +'</div>'+
					'</div>'+
					'<div class="editor-box-line-content">'+
						'<button class="editor-action" data-action="line-image" data-instance="'+ instanceId +'" title="'+ Editor.labels.image +'">'+
							Lime.Asset.icon('image') +
						'</button>'+
						'<button class="editor-action" data-action="show-editor-video" data-instance="'+ instanceId +'" title="'+ Editor.labels.video +'">'+ Lime.Asset.icon('camera-video-fill') +'</button>'+
						'<button class="editor-action" data-action="show-editor-grid" data-instance="'+ instanceId +'" title="'+ Editor.labels.grid +'">'+ Lime.Asset.icon('grid-fill') +'</button>'+
						'<button class="editor-action" data-action="quote" data-instance="'+ instanceId +'" title="'+ Editor.labels.quote +'">'+ Lime.Asset.icon('chat-quote-fill') +'</button>'+
						'<button class="editor-action" data-action="hr" data-instance="'+ instanceId +'" title="'+ Editor.labels.separator +'">'+ Lime.Asset.icon('dash') +'</button>'+
					'</div>'+
				'</div>';

			}

		html += '</div>';

		document.body.insertAdjacentHTML('beforeend', html);

	};

	static end(instanceId) {

		qs(instanceId +'-format', node => node.remove());

	};

	static reset(instanceId) {

		EditorFormat.hideMedia(instanceId);
		EditorFormat.hideSelection(instanceId);
		EditorFormat.hideLine(instanceId);

	};

	static currentMedia = {};

	static displayMedia(instanceId, figureId, mediaSelector) {

		const figureSelector = qs(figureId);

		EditorFormat.hideMedia(instanceId);

		let html = '<div class="editor-box-media">';
			html += '<div class="editor-box-media-content">';

			if(
				figureSelector.getAttribute('data-interactive') === 'true' &&
				(
					qsa(figureId+ ' > div[data-type]').length > 1 ||
					qsa(instanceId +' > figure[data-interactive="true"]').length > 1
				)
			) {
				html += '<a class="editor-action editor-box-media-action" data-action="media-move" data-instance="'+ instanceId +'" data-figure="'+ figureId +'" title="'+ Editor.labels.move +'" tabindex="-1">'+ Lime.Asset.icon('arrows-move') +'</a>';
			}

			if(
				figureSelector.getAttribute('data-interactive') === 'false' &&
				Editor.getBaseAncestor(figureSelector.parentElement) === null
			) {
				html += '<a class="editor-action editor-box-media-action" data-action="figure-move" data-instance="'+ instanceId +'" title="'+ Editor.labels.move +'" tabindex="-1">'+ Lime.Asset.icon('arrows-move') +'</a>';
			}


			if(mediaSelector.getAttribute('data-type') !== 'progress') {

				if(mediaSelector.getAttribute('data-xyz')) {

					let isCropable;

					if(
						(mediaSelector.getAttribute('data-xyz').slice(-1) === 'p' || mediaSelector.getAttribute('data-xyz').slice(-1) === 'j') &&
						mediaSelector.getAttribute('data-xyz-w') &&
						mediaSelector.getAttribute('data-xyz-h')
					) {

						const width = parseInt(mediaSelector.getAttribute('data-xyz-w'));
						const height = parseInt(mediaSelector.getAttribute('data-xyz-h'));

						const zoomFactor = Math.max(width, height) / Math.floor(2000 / ImageConf.imageCropRequiredFactor);

						isCropable = (zoomFactor > ImageConf.imageCropRequiredFactor);

					} else {
						isCropable = false;
					}

					if(isCropable) {
						html += '<a data-action="media-crop" class="editor-action editor-box-media-action" data-instance="'+ instanceId +'" data-type="editor" data-xyz="'+ mediaSelector.getAttribute('data-xyz') +'" title="'+ Editor.labels.crop +'"">'+ Lime.Asset.icon('crop') +'</a>';
					} else {
						html += '<a class="editor-action editor-box-media-action editor-box-media-action-disabled" title="'+ Editor.labels.cropResolution +'"">'+ Lime.Asset.icon('crop') +'</a>';
					}

				}

				if(mediaSelector.getAttribute('data-type') === 'image') {
					html += '<a data-action="media-configure" class="editor-action editor-box-media-action" data-instance="'+ instanceId +'" data-figure="'+ figureId +'" title="'+ Editor.labels.configure +'">'+ Lime.Asset.icon('gear-fill') +'</a>';
				}

			}

			html += '<a class="editor-action editor-box-media-action" data-action="media-remove" data-instance="'+ instanceId +'" data-figure="'+ figureId +'" title="'+ Editor.labels.delete +'" tabindex="-1">'+ Lime.Asset.icon('trash-fill') +'</a>';


			html += '</div>';
		html += '</div>';

		mediaSelector.insertAdjacentHTML('beforeend', html);

		EditorFormat.currentMedia[instanceId] = mediaSelector;

	};

	static hideMedia(instanceId) {

		qs(instanceId +' .editor-box-media', box => box.remove());

	};

	static updateLine(instanceId, range) {

		const instance = qs(instanceId);

		if(instance.getAttribute('data-figure') === '0') {
			return;
		}

		let selector = null;

		if(range !== null) {

			const rangeSelector = range.commonAncestorContainer;

			selector = Editor.getFirstAncestor(rangeSelector, 'P') || Editor.getFirstAncestor(rangeSelector, 'UL') || Editor.getFirstAncestor(rangeSelector, 'OL');

		}

		// Add selector if editor is empty (issue #2014)
		if(
			selector === null &&
			instance.childNodes.length === 1 &&
			EditorKeyboard.isEmptyLine(instance.firstChild)
		) {
			selector = instance.firstChild;
		}

		if(
			selector === null ||
			(selector.tagName === 'FIGURE' && selector.dataset.interative === 'true') ||
			(
				EditorKeyboard.isEmptyLine(range.commonAncestorContainer) === false &&
				window.matchMedia('(max-width: 767px)').matches
			)
		) {
			EditorFormat.hideLine(instanceId);
		} else {
			EditorFormat.placeLine(instance, instanceId +'-box-line', selector);
		}


	};

	static hideLine(instanceId) {

		const boxSelector = qs(instanceId +'-box-line');

		if(boxSelector !== null) {
			boxSelector.style.display = 'none';
			EditorFormat.actionLineClose(instanceId);
		}

	};

	static placeLine(instance, boxId, selector) {

		const nodeComputedStyle = getComputedStyle(instance);
		const nodeMarginLeft = parseInt(nodeComputedStyle.getPropertyValue('padding-left'));

		const currentRectangle = qs(boxId).getBoundingClientRect();
		const rectangle = selector.getBoundingClientRect();

		const currentTop = currentRectangle.top + window.scrollY;
		const currentLeft = currentRectangle.left + window.scrollX;

		const newTop = rectangle.top + window.scrollY - 3;

		let newLeft;

		if(window.matchMedia('(max-width: 699px)').matches) {
			newLeft = '';
		} else {
			newLeft = (rectangle.left - nodeMarginLeft - 39);
		}

		const helperSelector = qs(boxId +' div.editor-box-line-helper');

		helperSelector.style.marginLeft = nodeMarginLeft +'px';

		// Silent helper if needed
		if(EditorKeyboard.isEmptyLine(selector)) {

			if(helperSelector.style.visibility === 'hidden') {

				if(EditorFigure.isRightOf(selector) || EditorFigure.isLeftOf(selector)) {
					helperSelector.innerHTML = '';
				} else if(instance.childNodes.length === 1) {
					helperSelector.innerHTML = instance.getAttribute('data-placeholder-empty');
				} else {
					helperSelector.innerHTML = instance.getAttribute('data-placeholder-focus');
				}

				helperSelector.style.visibility = 'visible';

			}

		} else {

			if(helperSelector.style.visibility === 'visible') {
				helperSelector.style.visibility = 'hidden';
			}

		}

		// No change in position
		if(
			Math.abs(currentTop - newTop) < 2 && // Allow 2px variation
			(newLeft === '' || Math.abs(currentLeft - newLeft) < 2)
		) {
			return;
		}

		const boxSelector = qs(boxId);

		boxSelector.style.display = 'flex';
		boxSelector.style.top = newTop +'px';
		boxSelector.style.left = newLeft +'px';

		EditorFormat.action('line-close', '#'+ instance.id);

	};

	static displaySelection(instanceId, range) {

		const boxSelector = qs(instanceId + '-box-selection');

		const rectangle = range.getBoundingClientRect();

		const boxStyle = getComputedStyle(boxSelector);

		EditorFormat._restyleIcons(instanceId);

		boxSelector.style.display = 'flex';

		if(document.body.matches('[data-touch="no"]')) {

			boxSelector.style.top = (Math.floor(rectangle.top) + window.scrollY - parseInt(boxStyle.height) - 10) +'px';
			boxSelector.style.left = Math.max(10, (Math.floor(rectangle.left + rectangle.right - parseInt(boxStyle.width)) / 2)) +'px';

		}

	};

	static hideSelection(instanceId) {

		qs(instanceId +'-box-selection').style.display = 'none';

		EditorFormat._hideLink(instanceId);

	};

	static actionExplorer(instanceId) {
		window.open(qs(instanceId).attr('data-figure-full-url'), '_blank').focus();
	}

	static actionFigureRemove(instanceId, selectedElement) {
		const figureRemove = qs(selectedElement.getAttribute('data-figure'));
		EditorFigure.remove(instanceId, figureRemove);
	}

	static actionLineSwap(instanceId) {
		if(qs(instanceId +'-box-line').classList.contains('selected')) {
			EditorFormat.action('line-close', instanceId);
		} else {
			EditorFormat.action('line-open', instanceId);
		}
	}

	static actionLineOpen(instanceId) {
		qs(instanceId +'-box-line').classList.add('selected');
	}

	static actionLineClose(instanceId) {
		qs(instanceId +'-box-line').classList.remove('selected');
	}

	static actionLinkOpen(instanceId, selectedElement) {

		if(EditorFormat._hasStyle('link-open')) {

			EditorRange.saveState();

			const nodes = EditorRange.browseTags('A');

			if(nodes.length === 1) {

				EditorFormat._displayLink(instanceId, nodes[0]);

			} else {

				if(confirm(Editor.labels.confirmRemoveLinks)) {

					for(let i = 0; i < nodes.length; i++) {
						EditorFormat._removeLink(nodes[i]);
					}

				}

				EditorRange.applyState();

				EditorFormat._restyleIcons(instanceId);

			}

		} else {
			EditorFormat._displayLink(instanceId, null);
		}

	}

	static actionLinkClose(instanceId) {

		EditorRange.restore();

		EditorFormat._closeLink(instanceId);

	}

	static actionLink(instanceId) {

		EditorRange.restore();

		EditorFormat._createLink(instanceId);

		EditorFormat._hideLink(instanceId);

	}

	static actionHeader(instanceId) {

		EditorFormat._header(instanceId);

	}

	static actionStyle(instanceId, action) {

		if(qs(instanceId +'-box-selection .editor-action[data-action="'+ action +'"]').classList.contains('disabled')) {
			return;
		}

		document.execCommand('styleWithCSS', false, false);
		document.execCommand(action, false, null);


	}

	static actionAlign(instanceId, action) {
		EditorFormat._align(instanceId, action);
	}

	static actionHr(instanceId) {

		EditorHr.create(instanceId);
		EditorFormat.action('line-close', instanceId);

	}

	static actionQuote(instanceId) {

		EditorQuote.create(instanceId);
		EditorFormat.action('line-close', instanceId);

	}

	static actionLineImage(instanceId) {

		qs(instanceId +'-format .editor-upload', node => {
			node.removeAttribute('data-figure');
			node.click();
		});

	}

	static actionFigureImage(instanceId, selectedElement) {

		qs(instanceId +'-format .editor-upload', node => {
			node.setAttribute('data-figure', selectedElement.getAttribute('data-figure'));
			node.click();
		});

	}

	static actionMediaRemove(instanceId) {

		const mediaSelector = EditorFormat.currentMedia[instanceId];

		if(confirm(Editor.labels.confirmRemoveMedia)) {

			EditorFigure.removeNode(instanceId, mediaSelector);

		}

	}

	static actionMediaCrop(instanceId) {

		const mediaSelector = EditorFormat.currentMedia[instanceId];

		const hash = mediaSelector.getAttribute('data-xyz');

		new EditorMedia().getCropByHash(mediaSelector, hash);

	}

	static actionMediaConfigure(instanceId) {

		const mediaSelector = EditorFormat.currentMedia[instanceId];

		EditorFormat.configure(instanceId, mediaSelector);

	}

	static actionShowEditorGrid(instanceId, selectedElement) {

		EditorFormat.hideLine(instanceId);

		new Ajax.Query(selectedElement)
			.url('/editor/:configureGrid')
			.fetch()
			.then(() => {

				const grid = qs('#panel-editor-grid');

				grid.setAttribute('data-instance', instanceId);

			});

	}

	static actionGrid(instanceId) {

		const form = qs('#editor-grid-form');
		const columns = parseInt(form.qs('[name="columns"]').value);
		const rows = parseInt(form.qs('[name="rows"]').value);

		EditorGrid.create(instanceId, columns, rows);
		EditorFormat.action('line-close', instanceId);

		qs('#panel-editor-grid', node => Lime.Panel.close(node));

	}

	static actionShowEditorVideo(instanceId, selectedElement) {

		EditorFormat.hideLine(instanceId);

		new Ajax.Query(selectedElement)
			.url('/editor/:configureVideo')
			.fetch()
			.then(() => {

				const video = qs('#panel-editor-video');

				video.setAttribute('data-instance', instanceId);
				const figureId = selectedElement.getAttribute('data-figure');

				if(figureId) {
					video.setAttribute('data-figure', figureId);
				} else {
					video.removeAttribute('data-figure');
				}

			});

	}

	static actionVideo(instanceId) {

		const video = qs('#panel-editor-video');

		const figureId = video.dataset.figure;

		EditorVideo.convert(instanceId, figureId, qs('#editor-video-value').value);

		video.value = '';

		qs('#panel-editor-video', node => Lime.Panel.close(node));

		EditorFormat.action('line-close', instanceId);

	}

	static actionResize(instanceId, selectedElement) {

		const size = selectedElement.getAttribute('data-size');
		const figureSelector = qs(selectedElement.getAttribute('data-figure'));

		EditorFigure.resize(instanceId, figureSelector, size);

		EditorFigure.reorganize(instanceId, figureSelector);

	}

	static actionMoveMediaOpen(instanceId) {

		qsa(instanceId +' figure[data-interactive="true"]', figure => {

			new Sortable(figure, {
				handle: '.editor-box-media',
				group: 'figure',
				draggable: 'div.editor-media',
				chosenClass: 'editor-media-moving',
				onStart: function(e) {

					EditorFormat.hideMedia(instanceId);
					EditorFormat.startMoving(instanceId);

				},
				onChange: function(e) {

					// On laisse un placeholder si on vide la figure
					qsa(instanceId +' figure[data-interactive="true"]', figureCheck => {

						figureCheck.qsa('div.editor-media-placeholder', placeholder => placeholder.remove());

						if(figureCheck.qsa('div.editor-media').length === 0) {
							let clone = EditorFormat.currentMedia[instanceId].cloneNode(true);
							clone.childNodes.forEach(child => child.remove());
							clone.classList.add('editor-media-placeholder');
							figureCheck.insertAdjacentElement('afterbegin', clone);
						}
					});

					EditorFigure.reorganizeInstance(instanceId);

				},
				onEnd: function(e) {

					EditorFormat.action('move-figure-close', instanceId);
					EditorFigure.reorganizeInstance(instanceId);

					EditorFormat.stopMoving(instanceId);

				}
			});

		});

	}

	static actionMoveMediaClose(instanceId) {

		qsa(instanceId +' figure[data-interactive="true"]', figure => {
			Sortable.get(figure)?.destroy();
		});

	}

	static actionMoveFigureOpen(instanceId) {

		new Sortable(qs(instanceId), {
			fallbackOnBody: true,
			onChange: function(e) {
				EditorFigure.reorganizeInstance(instanceId);
			},
			onEnd: function(e) {

				EditorFormat.action('move-figure-close', instanceId);
				EditorFigure.reorganizeInstance(instanceId);

			}
		});

	}

	static actionMoveFigureClose(instanceId) {
		Sortable.get(qs(instanceId))?.destroy();
	}

	static action(action, instanceId, selectedElement) {

		switch(action) {

			case 'explorer' :
				return this.actionExplorer(instanceId);

			case 'figure-remove' :
				return this.actionFigureRemove(instanceId, selectedElement);

			case 'line-swap' :
				return this.actionLineSwap(instanceId);

			case 'line-open' :
				return this.actionLineOpen(instanceId);

			case 'line-close' :
				return this.actionLineClose(instanceId);

			case 'link-open' :
				return this.actionLinkOpen(instanceId, selectedElement);

			case 'link-close' :
				return this.actionLinkClose(instanceId);

			case 'link' :
				return this.actionLink(instanceId);

			case 'header' :
				return this.actionHeader(instanceId);

			case 'bold' :
			case 'italic' :
			case 'underline' :
				return this.actionStyle(instanceId, action);

			case 'align-left' :
			case 'align-right' :
			case 'align-center' :
			case 'align-justify' :
				return this.actionAlign(instanceId, action);

			case 'hr' :
				return this.actionHr(instanceId);

			case 'quote' :
				return this.actionQuote(instanceId);

			case 'line-image' :
				return this.actionLineImage(instanceId);

			case 'figure-image' :
				return this.actionFigureImage(instanceId, selectedElement);

			case 'media-remove' :
				return this.actionMediaRemove(instanceId);

			case 'media-crop' :
				return this.actionMediaCrop(instanceId);

			case 'media-configure':
				return this.actionMediaConfigure(instanceId);

			case 'show-editor-grid':
				return this.actionShowEditorGrid(instanceId, selectedElement);

			case 'grid' :
				return this.actionGrid(instanceId);

			case 'show-editor-video':
				return this.actionShowEditorVideo(instanceId, selectedElement);

			case 'video' :
				return this.actionVideo(instanceId);

			case 'resize' :
				return this.actionResize(instanceId, selectedElement);

			case 'move-media-open' :
				return this.actionMoveMediaOpen(instanceId);

			case 'move-media-close' :
				return this.actionMoveMediaClose(instanceId);

			case 'move-figure-open' :
				return this.actionMoveFigureOpen(instanceId);

			case 'move-figure-close' :
				return this.actionMoveFigureClose(instanceId);

		}

	};

	static isMoving() {
		return EditorFormat.mediaMoving;
	};

	static startMoving(instanceId) {

		EditorFormat.mediaMoving = true;

		qsa(instanceId +' figure[data-interactive="true"]', figure => {

			const figcaption = figure.qs('figcaption');

			if(figcaption === null) {
				return;
			}

			const paddingBottom = figcaption.offsetHeight + parseInt(getComputedStyle(figure).paddingBottom);

			figure.style.paddingBottom = paddingBottom +'px';
			figcaption.classList.add('figure-moving');

		});

	};

	static stopMoving(instanceId) {

		EditorFormat.mediaMoving = false;

		qsa(instanceId +' figure[data-interactive="true"]', figure => {

			const figcaption = figure.qs('figcaption');

			if(figcaption === null) {
				return;
			}

			figure.style.paddingBottom = '';
			figcaption.classList.remove('figure-moving');

		});

	};

	static configure(instanceId, mediaSelector) {

		new Ajax.Query(mediaSelector)
			.url('/editor/:configureMedia')
			.body({
				instanceId: instanceId,
				url: mediaSelector.getAttribute('data-url'),
				xyz: mediaSelector.getAttribute('data-xyz'),
				title: mediaSelector.getAttribute('data-title'),
				link: mediaSelector.getAttribute('data-link'),
				figureSize: mediaSelector.parentElement.querySelectorAll('div.editor-media[data-type]').length
			})
			.fetch();

	};

	static _align(instanceId, action) {

		const nodes = EditorRange.browseTags('P');

		if(nodes.length === 0) {
			return;
		}

		// Calc new alignment
		const align = action.substring(6);

		// Apply new alignment
		for(let i = 0; i < nodes.length; i++) {
			nodes[i].setAttribute('data-align', align);
		}

		EditorFormat._restyleIcons(instanceId);

		return true;

	};

	static _header(instanceId) {

		EditorRange.saveState();

		const nodes = EditorRange.browseTags('P');

		if(nodes.length === 0) {
			return;
		}

		// Calc new header size
		const firstNodeP = nodes[0];

		let newHeaderSize;

		if(firstNodeP.hasAttribute('data-header')) {
			newHeaderSize = parseInt(firstNodeP.getAttribute('data-header'));
			if(isNaN(newHeaderSize) || ++newHeaderSize > 2) {
				newHeaderSize = null;
			}
		} else {
			newHeaderSize = 0;
		}

		// Apply new header size
		for(let i = 0; i < nodes.length; i++) {

			const nodeP = nodes[i];

			if(newHeaderSize === null) {
				nodeP.removeAttribute('data-header');
				nodeP.removeAttribute('class');
			} else {

				if(nodeP.qsa('b, i, u, a').length > 0) {

					if(confirm(Editor.labels.confirmHeader) === false) {
						return false;
					} else {
						nodeP.qsa('b, i, u, a', nodeStyle => EditorNode.remove(nodeStyle));
					}

				}

				nodeP.setAttribute('data-header', newHeaderSize);
				nodeP.removeAttribute('class');

			}

		}

		EditorRange.applyState();

		EditorFormat._restyleIcons(instanceId);

		return true;

	};

	static nodeLink = null;

	static _displayLink(instanceId, node) {

		EditorFormat.nodeLink = node;

		qs(instanceId +'-box-selection .editor-box-selection-icons').style.display = 'none';
		qs(instanceId +'-box-selection .editor-box-selection-link').style.display = 'block';
		qs(instanceId +'-box-selection .editor-box-selection-link input').focus();

		if(node !== null) {

			qs(instanceId +'-box-selection [type="submit"]').innerHTML = Editor.labels.linkFilledSubmit;
			qs(instanceId +'-box-selection [data-action="link-close"]').innerHTML = Editor.labels.linkFilledClose;

			qs(instanceId +'-box-selection .editor-box-selection-link input').value = node.getAttribute('href');

		} else {

			qs(instanceId +'-box-selection [type="submit"]').innerHTML = Editor.labels.linkEmptySubmit;
			qs(instanceId +'-box-selection [data-action="link-close"]').innerHTML = Editor.labels.linkEmptyClose;

		}

	};

	static _closeLink(instanceId) {

		if(EditorFormat.nodeLink !== null) {
			if(confirm(Editor.labels.confirmRemoveLink)) {
				EditorFormat._removeLink(EditorFormat.nodeLink);
				EditorFormat._restyleIcons(instanceId);
			}
		}

		EditorFormat._hideLink(instanceId);

	};

	static _hideLink(instanceId) {

		qs(instanceId +'-box-selection .editor-box-selection-icons').style.display = 'block';
		qs(instanceId +'-box-selection .editor-box-selection-link').style.display = 'none';
		qs(instanceId +'-box-selection .editor-box-selection-link input').value = '';

		EditorFormat.nodeLink = null;

	};

	static _createLink(instanceId) {

		let link = qs(instanceId + '-box-selection .editor-box-selection-link input').value;

		if(link.match(/^http[s]?\:\/\//) === null) {
			link = 'http://'+ link;
		}

		if(EditorFormat.nodeLink === null) {

			document.execCommand('styleWithCSS', false, false);
			document.execCommand("createLink", false, link);

		} else {
			EditorFormat.nodeLink.setAttribute('href', link);
		}

	};

	static _removeLink(node) {

		const newElements = document.createDocumentFragment();

		while(node.childNodes.length > 0) {
			newElements.appendChild(node.childNodes[0]);
		}


		const updateStartContainer = (node === EditorRange.startContainer);
		const updateEndContainer = (node === EditorRange.endContainer);

		node.parentElement.replaceChild(newElements, node);

		if(updateStartContainer) {
			EditorRange.newStartContainer = newElements[0];
		}

		if(updateEndContainer) {
			EditorRange.newEndContainer = newElements[0];
		}

	};

	static _hasStyle(command, node) {

		switch(command) {

			case 'link-open' :

				const nodes = EditorRange.browseTags('A');
				return (nodes.length > 0);

			case 'header' :

				const nodeHeader = EditorRange.browseFirstTag('P');

				if(nodeHeader !== null) {
					return nodeHeader.hasAttribute('data-header') ? node : false
				} else {
					return false;
				}

			case 'align-left' :
			case 'align-center' :
			case 'align-right' :
			case 'align-justify' :

				const nodeAlign = EditorRange.browseFirstTag('P');

				if(nodeAlign !== null) {

					const selectedAlignement = nodeAlign.getAttribute('data-align');
					const commandAlignement = command.substring(6);

					if(selectedAlignement === 'left' || selectedAlignement === 'center' || selectedAlignement === 'right' || selectedAlignement === 'justify') {
						return (selectedAlignement === commandAlignement);
					} else {
						return ('left' === commandAlignement);
					}

				} else {
					return false;
				}

			default :
				return document.queryCommandState(command);


		}

	};

	static _restyleIcons(instanceId) {

		const nodes = EditorRange.browseTags('P');

		if(nodes.length === 0) {

			EditorFormat._iconHide(instanceId, 'align-left');
			EditorFormat._iconHide(instanceId, 'align-center');
			EditorFormat._iconHide(instanceId, 'align-right');
			EditorFormat._iconHide(instanceId, 'align-justify');
			EditorFormat._iconHide(instanceId, 'align-separator');

		} else {

			EditorFormat._iconStyle(instanceId, 'align-left');
			EditorFormat._iconStyle(instanceId, 'align-center');
			EditorFormat._iconStyle(instanceId, 'align-right');
			EditorFormat._iconStyle(instanceId, 'align-justify');

			EditorFormat._iconShow(instanceId, 'align-separator');

		}

		if(EditorFormat._iconStyle(instanceId, 'header')) {

			EditorFormat._iconDisable(instanceId, 'bold');
			EditorFormat._iconDisable(instanceId, 'italic');
			EditorFormat._iconDisable(instanceId, 'underline');
			EditorFormat._iconDisable(instanceId, 'link-open');

		} else {

			EditorFormat._iconStyle(instanceId, 'bold');
			EditorFormat._iconStyle(instanceId, 'italic');
			EditorFormat._iconStyle(instanceId, 'underline');

		}

	};

	static _iconHide(instanceId, command) {

		const selector = qs(instanceId + '-box-selection [data-action="' + command + '"]');
		selector.classList.add('hidden');

	};

	static _iconShow(instanceId, command) {

		const selector = qs(instanceId + '-box-selection [data-action="' + command + '"]');
		selector.classList.remove('hidden');

	};

	static _iconDisable(instanceId, command) {

		const selector = qs(instanceId + '-box-selection [data-action="' + command + '"]');

		selector.classList.remove('hidden');
		selector.classList.remove('selected');
		selector.classList.add('disabled');
		selector.childNodes.forEach(node => node.classList.remove('selected'));

	};

	static _iconStyle(instanceId, command) {

		const selector = qs(instanceId + '-box-selection [data-action="' + command + '"]');

		selector.classList.remove('hidden');
		selector.classList.remove('disabled');

		const result = EditorFormat._hasStyle(command);

		if(result) {

			switch(command) {

				case 'header' :
					selector.childNodes.forEach(node => node.classList.remove('selected'));
					selector.qs('span.editor-header').classList.add('selected');
					break;

				default :
					selector.classList.add('selected');
					break;

			}

			return true;

		} else {

			selector.classList.remove('selected');
			selector.childNodes.forEach(node => node.classList.remove('selected'));
			return false;

		}

	};

}

// Manipulates selected text
class EditorRange {

	// Last valid range pinged
	static last = null;

	// Ping update EditorRange.last variable
	static saveLast(range) {

		EditorRange.last = range;

		return EditorRange.last;

	};

	static get() {

		const currentSelection = window.getSelection();

		if(currentSelection.rangeCount === 0) {
			return null;
		}

		return currentSelection.getRangeAt(0);

	};

	// Check if range belongs to an instance EditorRange.last variable
	static isIn(range, id) {

		if(range === null) {
			return false;
		}

		let container = range.commonAncestorContainer;

		while(container) {

			if('#'+ container.id === id) {
				return true;
			}

			container = container.parentElement;

		}

		return false;

	};

	// Restore previous range
	static restore() {

		if(EditorRange.last !== null) {

			const selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange(EditorRange.last);

		}

	};

	// Replace current range at the end of the given node
	static replaceEnd(container) {

		const startContainer = container;
		let startOffset = container.childNodes.length;

		if(
			startContainer.childNodes.length > 0 &&
			startContainer.lastChild.nodeType === Node.ELEMENT_NODE &&
			startContainer.lastChild.tagName === 'BR'
		) {
			startOffset--;
		}

		return EditorRange.replace(startContainer, startOffset);

	};

	// Replace current range with new values
	static replace(startContainer, startOffset, endContainer, endOffset) {

		const newRange = document.createRange();

		if(
			typeof endContainer === 'undefined' || typeof endOffset === 'undefined' ||
			(startContainer === endContainer && startOffset === endOffset)
		) {

			newRange.setStart(startContainer, startOffset);
			newRange.collapse(true);

		} else {

			newRange.setStart(startContainer, startOffset);
			newRange.setEnd(endContainer, endOffset);

		}

		const selection = window.getSelection();
		selection.removeAllRanges();
		selection.addRange(newRange);

		EditorRange.last = newRange;

	};

	// Remove current range
	static remove() {

		const selection = window.getSelection();

		if(selection.rangeCount > 0) {
			selection.removeAllRanges();
		}

		EditorRange.last = null;

	};

	// Checks if a selection is a the beginning of a line
	// returns null if selection is not a the beginning
	// return the root node of the line otherwise
	static lineStart(selection) {

		if(selection.rangeCount === 0) {
			return null;
		}

		const range = selection.getRangeAt(0);

		// Range is not collapsed
		if(range.collapsed === false) {
			return null;
		}

		// Range does not start at offset 0
		if(range.startOffset > 0) {
			return null;
		}

		return EditorRange._lineCheck(range.startContainer, 'previousSibling');

	};

	// Checks if a selection is a the end of a contenteditable
	// returns null if selection is not a the end
	// return the root node of the line otherwise
	static lineEnd(selection) {

		if(selection.rangeCount === 0) {
			return null;
		}

		const range = selection.getRangeAt(0);

		// Range is not collapsed
		if(range.collapsed === false) {
			return null;
		}

		// If TEXT_NODE
		// Range does not start at the end of
		if(
			range.startContainer.nodeType === Node.TEXT_NODE &&
			range.startOffset !== range.startContainer.nodeValue.length
		) {
			return null;
		}

		return EditorRange._lineCheck(range.startContainer, 'nextSibling');

	};

	static _lineCheck(node, sibling) {

		if(Editor.isBaseNode(node)) {

			return node;

		} else {

			for(; ; ) {

				const nodeSibling = EditorNode.siblingNoWhitespaces(node, sibling);

				// Node has a sibling, so we are not at the beginning/end of the line
				if(nodeSibling !== null) {
					return null;
				}

				node = node.parentElement;

				if(node === null) {
					return null;
				}

				// Top of a text in figure

				// Figcaption
				if(node.tagName === 'FIGCAPTION') {
					return node;
				}
				// Editor and blockquote
				if(node.parentElement.getAttribute('contenteditable') === 'true') {
					return node;
				}

			}

			return null;

		}

	};

	// Browse each node of the current range
	static browse(callback) {

		const current = window.getSelection().getRangeAt(0).cloneRange();

		if(
			current.collapsed ||
			current.startContainer === current.endContainer
		) {

			EditorRange._browseAncestors(current.startContainer, callback);

		} else {

			let node = current.startContainer;
			const nodeEnd = current.endContainer;

			// Browse node until we match the common ancestor container
			try {

				while(node !== null) {

					// Browse siblings
					EditorRange._browseSiblings(node, nodeEnd, callback);

					if(node.parentElement === current.commonAncestorContainer) {
						throw 'end';
					}

					callback(node.parentElement);

					node = node.parentElement.nextSibling;

				}

			} catch(e) {
				if(e !== 'end') {
					throw e;
				}
			}


			EditorRange._browseAncestors(current.commonAncestorContainer, callback);

		}

	};

	// Browse parent tag of the current range
	static browseFirstTag(tag) {

		let nodeFound = null;

		try {

			EditorRange.browse(function(node) {

				if(node.nodeName === tag) {
					nodeFound = node;
					throw 'found';
				}

			});

		} catch(e) {
			if(e !== 'found') {
				throw e;
			}
		}

		return nodeFound;

	};

	static browseTags(tag) {

		const nodesFound = [];

		EditorRange.browse(function(node) {

			if(node.nodeName === tag) {
				nodesFound[nodesFound.length] = node;
			}

		});

		return nodesFound;

	};

	static _browseSiblings(node, nodeEnd, callback) {

		let sibling = node;

		for(; ; ) {

			if(sibling.nodeType === Node.ELEMENT_NODE) {

				callback(sibling);

				if(sibling.childNodes.length > 0) {
					EditorRange._browseSiblings(sibling.childNodes[0], nodeEnd, callback);
				}


			}

			if(sibling === nodeEnd) {
				throw 'end';
			}

			if(sibling.nextSibling === null) {
				break;
			}

			sibling = sibling.nextSibling;

		}

	};

	static _browseAncestors(node, callback) {

		// Ignore text nodes
		if(node.nodeType === Node.TEXT_NODE) {
			node = node.parentElement;
		}

		while(
			// Root node (contenteditable div)
			// Stop looping
			node &&
			Editor.isEditorNode(node) === false
		) {

			callback(node);

			node = node.parentElement;

		}

	};

	static state = false;

	static startContainer = null;
	static newStartContainer = null;
	static startOffset = 0;

	static endContainer = null;
	static newEndContainer = null;
	static endOffset = 0;

	static saveState() {

		// Prepare a new range
		if(window.getSelection().rangeCount > 0) {

			const newRange = window.getSelection().getRangeAt(0).cloneRange();

			EditorRange.startContainer = newRange.startContainer;
			EditorRange.startOffset = newRange.startOffset;
			EditorRange.endContainer = newRange.endContainer;
			EditorRange.endOffset = newRange.endOffset;

		} else {

			EditorRange.startContainer = null;
			EditorRange.startOffset = 0;
			EditorRange.endContainer = null;
			EditorRange.endOffset = 0;

		}

		EditorRange.newStartContainer = null;
		EditorRange.newEndContainer = null;

		EditorRange.state = true;

	};

	static isStateSaved() {
		return EditorRange.state;
	};

	static applyState() {

		if(EditorRange.newStartContainer !== null || EditorRange.newEndContainer !== null) {

			const newStartContainer = EditorRange.newStartContainer ? EditorRange.newStartContainer : EditorRange.startContainer;
			const newEndContainer = EditorRange.newEndContainer ? EditorRange.newEndContainer : EditorRange.endContainer;

			// Refresh range
			if(newStartContainer !== null && newEndContainer !== null) {

				EditorRange.replace(
					newStartContainer, EditorRange.startOffset,
					newEndContainer, EditorRange.endOffset
				);

				EditorRange.newStartContainer = null;
				EditorRange.newEndContainer = null;

			}

		}

		EditorRange.state = false;

	};

};

// Tools for figure handling
class EditorFigure {

	static counter = 0;

	static select(instanceId, figureSelector) {

		const figureId = '#' + figureSelector.id;

		if(
			figureSelector.getAttribute('data-interactive') === 'false' || // Figure is not interactive
			figureSelector.qs('.editor-box-figure') !== null // Figure box already exists
		) {
			return;
		}

		EditorFigure.deselect(instanceId);

		const boxId = figureId + '-box-figure';

		if(qs(boxId) === null) {

			let html = '<div id="'+ boxId.substring(1) +'" class="editor-box-figure" data-whitespace="1">';
				html += '<div class="editor-box-figure-container">';
					html += '<button class="editor-action editor-action-plus" data-action="figure-image" data-instance="'+ instanceId +'" title="'+ Editor.labels.imageFigure +'">'+ Lime.Asset.icon('image') +''+ Lime.Asset.icon('plus-circle') +'<span class="editor-box-figure-label">'+ Editor.labels.imageLabel +'</span></button>';
					html += '<span class="separator">&nbsp;</span>';
					html += '<button class="editor-action editor-action-plus" data-action="show-editor-video" data-instance="'+ instanceId +'" title="'+ Editor.labels.videoFigure +'">'+ Lime.Asset.icon('camera-video-fill') +''+ Lime.Asset.icon('plus-circle') +'<span class="editor-box-figure-label">'+ Editor.labels.videoLabel +'</span></button>';
					html += '<span class="separator">&nbsp;</span>';
					html += '<div>';
						html += '<button class="dropdown-toggle" data-dropdown="bottom-start" title="'+ Editor.labels.resize +'">'+ Lime.Asset.icon('grid-fill') +'<span class="editor-box-figure-label hide-md-down">'+ Editor.labels.resizeLabel +'</span></button>';
						html += '<div class="dropdown-list">';
							html += '<button class="dropdown-item" data-action-run="1" data-action="resize" data-size="compressed" data-instance="'+ instanceId +'" data-figure="'+ figureId +'">'+ Editor.labels.resizeCompress +'</button>';
							html += '<button class="dropdown-item" data-action-run="1" data-action="resize" data-size="left" data-instance="'+ instanceId +'" data-figure="'+ figureId +'">'+ Editor.labels.resizeLeft +'</button>';
							html += '<button class="dropdown-item" data-action-run="1" data-action="resize" data-size="right" data-instance="'+ instanceId +'" data-figure="'+ figureId +'">'+ Editor.labels.resizeRight +'</button>';
						html += '</div>';
					html += '</div>';

					html += '<span class="separator">&nbsp;</span>';
					html += '<a class="editor-action" data-action="figure-move" data-instance="'+ instanceId +'" title="'+ Editor.labels.moveFigure +'" tabindex="-1">'+ Lime.Asset.icon('arrows-move') +'</a>';
					html += '<span class="separator">&nbsp;</span>';
					html += '<button class="editor-action" data-action="figure-remove" data-instance="'+ instanceId +'" title="'+ Editor.labels.removeFigure +'">'+ Lime.Asset.icon('trash-fill') +'</button>';
				html += '</div>';
			html += '</div>';

			html += '<div class="editor-box-figcaption" data-whitespace="1">';
				html += '<span class="editor-action" title="'+ Editor.labels.captionLimit +'">?</span>/'+ Editor.conf.captionLimit;
			html += '</div>';

			figureSelector.insertAdjacentHTML('beforeend', html);

			EditorFigure.updateCaption(figureSelector);

		}

		qsa(boxId +' [data-action="resize"]', node => node.classList.remove('selected'));
		qs(boxId +' [data-action="resize"][data-size="'+ figureSelector.getAttribute('data-size') +'"]', node => node.classList.add('selected'));

		const medias = qsa(figureId + ' > div[data-type]').length;

		if(medias > 1) {
			qs(boxId +' [data-action="resize"][data-size="left"]').style.display = 'none';
			qs(boxId +' [data-action="resize"][data-size="right"]').style.display = 'none';
		} else {
			qs(boxId +' [data-action="resize"][data-size="left"]').style.display = 'block';
			qs(boxId +' [data-action="resize"][data-size="right"]').style.display = 'block';
		}

		qsa(boxId +' .editor-action', action => action.setAttribute('data-figure', figureId));

		if(
			qs(figureId +' figcaption:focus') === null &&
			qs(figureId +' blockquote:focus') === null
		) {
			EditorRange.remove();
		}

		figureSelector.classList.add('selected');

	};

	static deselect(instanceId) {

		const instance = qs(instanceId);

		if(instance === null) {
			return;
		}

		const figureSelector = EditorFigure.getSelected(instance);

		if(figureSelector !== null) {

			figureSelector.classList.remove('selected');

			EditorFormat.action('move-media-close', instanceId);

		}

		qs(instanceId +' .editor-box-figure')?.remove();
		qs(instanceId +' .editor-box-figcaption')?.remove();

	};

	// Returns selected figure of an instance
	static getSelected(instanceSelector) {
		return instanceSelector.qs('figure.selected');
	};

	static selectCaption(instanceId) {

		qs(instanceId +' .editor-box-figcaption', figcaption => {

			if(figcaption.isHidden()) {
				figcaption.style.display = 'block';
			}

		});


	};

	static deselectCaption(instanceId) {

		qs(instanceId +' .editor-box-figcaption', figcaption => {

			if(figcaption.isVisible()) {
				figcaption.style.display = 'none';
			}

		});

	};

	static updateCaption(figureSelector) {

		let length = figureSelector.qs('figcaption').textContent.length;
		const maxLength = Editor.conf.captionLimit;

		figureSelector.qs('.editor-box-figcaption > span', (span) => {

			if(length > maxLength) {
				span.style.color = 'red';
				length = Lime.Asset.icon('exclamation-triangle-fill') +' '+ length;
			} else {
				span.style.color = '';
			}

			span.innerHTML = length;

		});

	};

	// Create a new and empty figure
	static create(instanceId, options) {

		options = options || {};

		let nodeHost;

		if(options.nodeHost) {
			nodeHost = options.nodeHost;
		} else if(EditorRange.last && EditorRange.last.commonAncestorContainer) {
			nodeHost = EditorRange.last.commonAncestorContainer;
		} else {
			nodeHost = qs(instanceId).lastChild;
		}

		const interactive = typeof options.interactive !== 'undefined' ? options.interactive : true;

		const nodeFigureId = 'editor-figure-' + EditorFigure.counter++;
		const nodeFigure = document.createElement('figure');
		nodeFigure.setAttribute('data-widget', 'figure');
		nodeFigure.setAttribute('data-caption', 'true');
		nodeFigure.setAttribute('data-interactive', interactive ? 'true' : 'false');
		nodeFigure.setAttribute('contenteditable', 'false');
		nodeFigure.setAttribute('id', nodeFigureId);

		if(interactive) {
			nodeFigure.setAttribute('data-size', 'compressed');
		}

		let node;

		if(Editor.isEditorNode(nodeHost)) {
			node = EditorParagraph.getEmpty();
			nodeHost.appendChild(node);
		} else {

			const baseNode = Editor.getBaseAncestor(nodeHost) || nodeHost;

			if(EditorKeyboard.isEmptyLine(baseNode)) {
				node = baseNode;
			} else {
				node = EditorParagraph.getEmpty();
				baseNode.insertAdjacentElement('afterend', node);
			}

		}

		if(node.parentElement !== null) {

			node.parentElement.replaceChild(nodeFigure, node);



			// A figure can't be the last node of a contenteditable
			if(nodeFigure.nextSibling === null) {

				const nodeP = EditorParagraph.getEmpty();

				nodeFigure.insertAdjacentElement('afterend', nodeP);

			}

			// Add figcaption for interactive figures
			if(interactive) {

				const nodeFigcaption = document.createElement('figcaption');
				nodeFigcaption.setAttribute('contenteditable', 'true');
				nodeFigcaption.setAttribute('placeholder', Editor.labels.captionFigure);

				nodeFigure.appendChild(nodeFigcaption);

			}

			EditorFigure.updateHack(nodeFigure);

			return '#'+ nodeFigureId;

		} else {
			return null;
		}

	};

	// Remove an existing figure
	static remove(instanceId, figureSelector, noConfirm) {

		if(
			noConfirm ||
			confirm(Editor.labels.confirmRemoveFigure)
		) {

			figureSelector.remove();

			EditorFigure.deselect(instanceId);

		}

	};

	// Resize an existing figure
	static resize(instanceId, figureSelector, size) {

		const figureId = '#'+ figureSelector.id;

		qsa('[data-figure="'+ figureId +'"][data-action="resize"]', node => node.classList.remove('selected'));
		qs('[data-figure="'+ figureId +'"][data-action="resize"][data-size="'+ size +'"]').classList.add('selected');

		figureSelector.setAttribute('data-size', size);

		figureSelector.qs('figcaption').style.display = (size === 'left' || size === 'right') ? 'none' : 'block';

		Lime.Dropdown.closeAll();

	};

	// Add a selector in a figure
	static addSelector(instanceId, figureSelector, mediaSelector) {

		const figcaptionSelector = figureSelector.qs('figcaption');

		if(figcaptionSelector !== null) {
			figcaptionSelector.insertAdjacentElement('beforebegin', mediaSelector);
		} else {
			figureSelector.insertAdjacentElement('beforeend', mediaSelector);
		}

		EditorFigure.updateHack(figureSelector);

		if(figcaptionSelector !== null) {
			figcaptionSelector.style.display = 'block';
		}

		// Reorganize all nodes
		EditorFigure.reorganize(instanceId, figureSelector);

		// Figure actions may have change
		EditorFigure.select(instanceId, figureSelector);

	};

	// Add a node in a figure
	static addNode(instanceId, figureId, nodeValue, type, defaultWidth, defaultHeight, attributes) {

		const node = document.createElement('div');
		node.setAttribute('class', 'editor-media');
		node.setAttribute('data-type', type);

		if(defaultWidth !== null) {
			node.setAttribute('data-w', defaultWidth);
		}

		if(defaultHeight !== null) {
			node.setAttribute('data-h', defaultHeight);
		}

		EditorFigure._applyAttributes(node, attributes);
		EditorFigure._applyValue(node, nodeValue);

		const figureSelector = qs(figureId);

		// If figure is displayed left, center it
		if(figureSelector.getAttribute('data-size') === 'left' || figureSelector.getAttribute('data-size') === 'right') {
			EditorFigure.resize(instanceId, figureSelector, 'compressed');
		}


		EditorFigure.addSelector(instanceId, figureSelector, node);

		return node;

	};

	// Replace an existing node in a figure
	static replaceNode(node, nodeValue, type, attributes) {

		node.setAttribute('data-type', type);

		EditorFigure._applyAttributes(node, attributes);
		EditorFigure._applyValue(node, nodeValue);

	};

	static _applyValue(node, nodeValue) {

		while(node.childNodes.length > 0) {
			node.removeChild(node.firstChild);
		}

		if(nodeValue !== null) {

			if(typeof nodeValue === 'string') {
				node.innerHTML = nodeValue;
			} else {
				node.appendChild(nodeValue);
			}
		}

	};

	static _applyAttributes(node, attributes) {

		if(typeof attributes !== 'undefined') {
			for(let name in attributes) {
				node.setAttribute('data-'+ name, attributes[name]);
			}
		}

	};

	// Hack to avoid selection issue in figures
	static updateHack(figureSelector) {

		figureSelector.childNodes.filter('span').forEach(node => node.remove());

		const nodeSpan = document.createElement('span');
		nodeSpan.setAttribute('contenteditable', 'false');

		figureSelector.appendChild(nodeSpan);

	};

	// Remove a node from a figure
	static removeNode(instanceId, nodeSelector) {

		const figureSelector = nodeSelector.parentElement;

		nodeSelector.remove();

		// Reorganize all nodes
		EditorFigure.reorganize(instanceId, figureSelector);

		// Figure actions may have change
		EditorFigure.select(instanceId, figureSelector);

	};

	// Reorganize nodes in all figures of an instance
	static reorganizeInstance(instanceId) {

		qsa(instanceId +' figure[data-interactive="true"]', figureSelector => {
			EditorFigure.reorganize(instanceId, figureSelector);
		});

	};

	// Reorganize nodes in a figure
	// Drop figure if it has no node any more
	static reorganize(instanceId, figureSelector) {

		const children = figureSelector.qsa('.editor-media').length;

		if(children === 0) {
			EditorFigure.remove(instanceId, figureSelector, true);
			return;
		}

		if(figureSelector.getAttribute('data-interactive') === 'true') {

			new GalleryEditor(figureSelector, {
				container: '.editor-media',
				onReorganized: () => {
					ReaditorFigure.reorganize(figureSelector);
				}
			}).listenResize();

		}

	};

	// Insert an empty line before a figure
	static insertLineBefore(instanceId, figureSelector) {

		const node = EditorParagraph.getEmpty();

		figureSelector.parentElement.insertBefore(node, figureSelector);

		EditorRange.replace(node, 0);

	};

	// Check if this TOP selector is right of a data-size="left" figure
	static isRightOf(selector) {
		return EditorFigure.isPositionOf(selector, 'left')
	};

	// Check if this TOP selector is right of a data-size="right" figure
	static isLeftOf(selector) {
		return EditorFigure.isPositionOf(selector, 'right')
	};

	// Check if this TOP selector is right of a data-size="left" figure
	static isPositionOf(selector, compare) {

		let figureSelector = null;
		let currentSibling = selector;

		while(currentSibling.previousSibling !== null) {

			currentSibling = currentSibling.previousSibling;

			if(
				currentSibling.tagName === 'FIGURE' &&
				currentSibling.getAttribute('data-size') === compare
			) {
				figureSelector = currentSibling;
				break;
			}

		}

		if(figureSelector === null) {
			return false;
		}

		const rectangle = selector.getBoundingClientRect();
		const figureRectangle = figureSelector.getBoundingClientRect();

		return (figureRectangle.bottom >= rectangle.top);

	};

};

// Handle hr in the editor
class EditorHr {

	static create(instanceId) {

		const figureId = EditorFigure.create(instanceId, {
			interactive: false
		});

		if(figureId === null) {
			return;
		}

		const nodeHr = document.createElement('div');
		nodeHr.setAttribute('class', 'editor-hr');
		nodeHr.innerHTML = '&#149; &#149; &#149;';

		EditorFigure.addNode(instanceId, figureId, nodeHr, 'hr', null, null, {});
	};

}

// Handle quotes in the editor
class EditorQuote {

	static create(instanceId) {

		const figureId = EditorFigure.create(instanceId, {
			interactive: false
		});

		if(figureId === null) {
			return;
		}

		const icons = Editor.labels['quoteIcons'];
		const firstIcon = Object.keys(icons)[0];

		const nodeQuote = document.createElement('div');
		nodeQuote.setAttribute('class', 'editor-quote');

		const nodeImage = document.createElement('div');
		nodeImage.setAttribute('class', 'editor-quote-image');
		nodeImage.setAttribute('data-action', 'quote-image');
		nodeImage.setAttribute('title', Editor.labels.quoteIcon);
		nodeImage.setAttribute('placeholder', icons[firstIcon]['label']);
		nodeImage.innerHTML = Lime.Asset.icon(icons[firstIcon]['icon']);

		nodeQuote.appendChild(nodeImage);

		const nodeBlockquote = document.createElement('blockquote');
		nodeBlockquote.setAttribute('contenteditable', 'true');
		nodeBlockquote.setAttribute('tabindex', '-1');

		const nodeP = EditorParagraph.getEmpty();
		nodeBlockquote.appendChild(nodeP);

		nodeQuote.appendChild(nodeBlockquote);

		EditorFigure.addNode(instanceId, figureId, nodeQuote, 'quote', null, null, {'about': firstIcon});
		EditorRange.replace(nodeP, 0);

	};

	static next(imageSelector) {

		const quoteSelector = imageSelector.parentElement.parentElement;
		const icons = Editor.labels['quoteIcons'];

		let newIcon;

		if(quoteSelector.dataset.about !== undefined) {

			const iconCurrent = quoteSelector.dataset.about;
			let iconFirst = null;
			let iconSelectNext = 'search';

			Object.entries(icons).forEach(([key, value]) => {

				if(iconFirst === null) {
					iconFirst = key;
				}

				if(iconSelectNext == 'pick') {
					newIcon = key;
					iconSelectNext = 'done';
				}

				if(key === iconCurrent) {
					iconSelectNext = 'pick';
				}

			});

			if(iconSelectNext === 'pick') {
				newIcon = iconFirst;
			}

		} else {
			newIcon = 'quote';
		}

		quoteSelector.setAttribute('data-about', newIcon);
		imageSelector.setAttribute('placeholder', icons[newIcon]['label']);
		imageSelector.innerHTML = Lime.Asset.icon(icons[newIcon]['icon']);


	};

}

// Handle grids in the editor
class EditorGrid {

	static create(instanceId, columns, rows) {

		const figureId = EditorFigure.create(instanceId, {
			interactive: false
		});

		if(figureId === null) {
			return;
		}

		columns = Math.min(Math.max(2, columns), 4);
		rows = Math.min(Math.max(1, rows), 5);

		const cells = columns * rows;

		const nodeGrid = document.createElement('div');
		nodeGrid.setAttribute('class', 'editor-grid');
		nodeGrid.setAttribute('data-columns', columns);

		for(let i = 0; i < cells; i++) {
			nodeGrid.appendChild(this.newCell());
		}

		EditorFigure.addNode(instanceId, figureId, nodeGrid, 'grid');

	};

	static newCell() {

		const nodeCell = document.createElement('div');
		nodeCell.setAttribute('contenteditable', 'true');
		nodeCell.setAttribute('tabindex', '-1');

		const nodeP = EditorParagraph.getEmpty();
		nodeCell.appendChild(nodeP);

		return nodeCell;

	}

}

// Handle videos in the editor
class EditorVideo {

	static convert(instanceId, figureId, url) {

		const data = EditorVideo.youtube(url) || EditorVideo.dailymotion(url) || EditorVideo.vimeo(url);

		if(data !== null) {

			if(typeof figureId === 'undefined') {
				figureId = EditorFigure.create(instanceId);
			}

			const nodeVideo = document.createElement('iframe');
			nodeVideo.setAttribute('frameborder', '0');
			nodeVideo.setAttribute('allowfullscreen', 'true');
			nodeVideo.setAttribute('src', data.url);

			EditorFigure.addNode(instanceId, figureId, nodeVideo, 'video', 854, 480, data);

		}

	};

	static youtube(url) {

		const re = /^(https?:\/\/(www\.)?youtu(\.)?be(\.com)?\/(watch\?v=|v\/|user\/|embed\/)?([a-zA-Z0-9_\-]*)(&|#|\?)?([^\s\[\\]*))$/;
		const match = re.exec(url);

		if(match === null) {
			return null;
		}

		const idVideo = match[6];

		return {
			source: 'youtube',
			url: 'https://www.youtube.com/embed/' + idVideo +'?rel=0'
		};

	};

	static dailymotion(url) {

		const re = /^(https?:\/\/(www\.)?dai(lymotion\.com|\.ly)\/(embed\/video\/|video\/|hub\/)?([a-zA-Z0-9_\-]*)(_.*)?)$/;
		const match = re.exec(url);

		if(match === null) {
			return null;
		}

		const idVideo = match[5];

		return {
			source: 'dailymotion',
			url: 'https://www.dailymotion.com/embed/video/' + idVideo
		};

	};

	static vimeo(url) {

		const re = /^https?:\/\/(www\.)?vimeo\.com\/([0-9]*)(#t=.*)?$/;
		const match = re.exec(url);

		if(match === null) {
			return null;
		}

		const idVideo = match[2];

		return {
			source: 'vimeo',
			url: '//player.vimeo.com/video/' + idVideo
		}
	};

};

// Handle paragraphes
class EditorParagraph {

	static counter = Date.now();

	// Creates and returns an empty line
	static getEmpty() {

		const node = document.createElement('p');
		node.setAttribute('data-location', EditorParagraph.tick());
		node.appendChild(document.createElement('br'));

		return node;

	};

	static tick() {
		EditorParagraph.counter++;
		return EditorParagraph.counter;
	};

	// Repair a paragraph node
	static repair(node) {

		// Add <br> if empty and missing
		if(node.childNodes.length === 0) {

			const nodeBr = document.createElement('br');
			node.appendChild(nodeBr);

		}

		// Add tick
		if(
			node.hasAttribute('data-location') === false &&
			(node.parentElement === null || node.parentElement.tagName !== 'BLOCKQUOTE')
		) {
			node.setAttribute('data-location', EditorParagraph.tick());
		}

	};

};

// Handle images in the editor
class EditorImage {

	static upload(instanceId, figureId, e, files) {

		const speed = qs(instanceId).getAttribute('data-speed');

		function onLoad(resolve, file, position, img) {

			EditorFormat.action('line-close', instanceId);

			// Get figure that will contain the image
			if(figureId === null) {
				figureId = EditorFigure.create(instanceId);
			}

			const previewUrl = ImageLoader.getPreview(file, img, 750, 80);

			let progressCss;
			if(previewUrl === null) {
				progressCss = 'background-color: #ccc';
			} else {
				progressCss = 'background-image: url(' + previewUrl + ')';
			}

			const progressId = 'progress-' + new Date().getTime();
			const progressHtml = ImageLoader.start(progressId, progressCss);

			const figureNode = EditorFigure.addNode(instanceId, figureId, progressHtml, 'progress', img.naturalWidth, img.naturalHeight);

			if(speed === 'slow') {

				const resizedUrl = ImageLoader.getPreview(file, img, 2000, 88);

				if(resizedUrl !== null) {
					file = ImageLoader.dataUrlToFile(resizedUrl);
				}

			}

			const data = new FormData();
			data.append('file', file);
			data.append('type', 'editor');

			const onFail = () => {

				ImageLoader.remove(progressId);
				EditorFigure.removeNode(instanceId, figureNode);

				resolve();

			};

			new (class extends Ajax.Query {

				done(data) {

					const media = data.media;

					const extension = media.hash.slice(-1);

					let url;
					if(extension === 'g') { // Original file for GIF
						url = media.urls.original;
					} else {
						url = media.urls.xl;
					}

					const nodeAttributes = {
						'url': url,
						'xyz': media.hash,
						'xyz-w': media.width,
						'xyz-h': media.height,
						'xyz-version': media.version,
						'color': media.color,
					};

					// Apply attributes to temporary node
					figureNode.setAttribute('data-type', 'image');
					EditorFigure._applyAttributes(figureNode, nodeAttributes);

					ImageLoader.remove(progressId);
					resolve();

					// Load final image
					const nodeDisplay = new Image();
					nodeDisplay.src = url;
					nodeDisplay.onload = function() {

						const nodeImage = document.createElement('div');
						nodeImage.setAttribute('class', 'editor-image');
						nodeImage.appendChild(nodeDisplay);

						EditorFigure.replaceNode(figureNode, nodeImage, 'image', nodeAttributes);

					}

				};

				showErrors(data) {

					if(data.errors) {
						Lime.Alert.showStaticErrors(data.errors);
					} else {
						Lime.Alert.showStaticError(ImageMessage['internalUpload']);
					}

					onFail();

				};

			})()
				.url('/media/:put')
				.body(data)
				.xmlHttpRequest(xhr => {

					xhr.upload.addEventListener("progress", function(e) {

						if(e.lengthComputable) {

							const percentComplete = Math.floor((e.loaded / e.total) * 100);
							ImageLoader.update(progressId, percentComplete);

						}

					}, false);

				}).then(null, () => onFail());

		};

		function onError(resolve) {
			resolve();
		};

		let parallel;

		if(speed === 'fast') {
			parallel = 4;
		} else {
			parallel = 2;
		}

		ImageStorage.upload(files, 50, 'editor', onLoad, onError, parallel);

	};

	static download(url) {

		url = url.replace(/\/editor\/([0-9]{3,4})\//, '/editor/');

		if(url.indexOf('?') > -1) {
			url += '&';
		} else {
			url += '?';
		}
		window.open(url + 'download=1');

	};

};

// Observe mutation in the contenteditable div
class EditorMutation {

	static observers = {};

	static observe(instanceId) {

		if(typeof EditorMutation.observers[instanceId] !== 'undefined') {
			return;
		}

		const config = {
			childList: true,
			characterData: true,
			subtree: true
		};

		let observer;

		observer = new MutationObserver(function(mutations) {

			// Get updated nodes
			const nodes = new Array;

			const instance = document.querySelector(instanceId);

			if(instance === null) {
				EditorMutation.disconnect(instanceId);
				return;
			}

			// Temporary shutdown the observer as we can change the dom
			observer.disconnect();

			EditorRange.saveState();

			mutations.forEach(function(mutation) {

				// Cleanup because browser can add/delete internal nodes
				for(let i = 0; i < mutation.addedNodes.length; i++) {
					nodes[nodes.length] = mutation.addedNodes[i];
				}

				for(let i = 0; i < mutation.removedNodes.length; i++) {
					const nodeKey = nodes.indexOf(mutation.removedNodes[i]);
					if(nodeKey !== -1) {
						delete nodes[nodeKey];
					}
				}

				const targetNode = mutation.target;

				if(
					nodes.indexOf(targetNode) === -1 &&
					Editor.isEditorNode(targetNode) === false
				) {
					nodes[nodes.length] = targetNode;
				}

			});

			nodes.forEach(function(node) {

				if(node.nodeType === Node.ELEMENT_NODE) {
					EditorMutation._nodeElement(instanceId, node);
				} else if(node.nodeType === Node.TEXT_NODE) {
					EditorMutation._nodeText(instanceId, node);
				}

			});

			EditorMutation._organizeInstance(instance);

			EditorRange.applyState();

			// Wakeup observer
			observer.observe(target, config);

		});

		// select the target node
		const target = document.querySelector(instanceId);

		// pass in the target node, as well as the observer options
		observer.observe(target, config);

		EditorMutation.observers[instanceId] = observer;

	};

	static disconnect(instanceId) {

		if(typeof EditorMutation.observers[instanceId] === 'undefined') {
		}

		EditorMutation.observers[instanceId].disconnect();

		delete EditorMutation.observers[instanceId];

	};

	static _organizeInstance(instance) {

		let currentP = null;

		for(let i = 0; i < instance.childNodes.length; i++) {

			const node = instance.childNodes[i];

			// Garbage collector for nasty browsers
			// Destroy empty lists and p
			if(browser.isSafari === false) {

				if(
					(node.tagName === 'OL' || node.tagName === 'UL' || node.tagName === 'P') &&
					node.innerHTML === ''
				) {
					node.parentElement.removeChild(node);
					i--;
					continue;
				}

			}

			// Put orphan nodes in paragraph
			if(
				(
					node.nodeType === Node.TEXT_NODE && (
						(node.nodeValue.trim() === '' && currentP !== null) ||
						node.nodeValue.trim() !== ''
					)
				) ||
				(node.nodeType === Node.ELEMENT_NODE && node.tagName !== 'OL' && node.tagName !== 'UL' && node.tagName !== 'BLOCKQUOTE' && node.tagName !== 'FIGURE' && node.tagName !== 'P')
			) {

				if(currentP === null) {

					currentP = document.createElement('P');

					instance.insertBefore(currentP, node);
					i++;

				}

				currentP.appendChild(node);
				i--;


			} else {
				currentP = null;
			}

		}

		// Check that last line if an empty <p>
		if(
			instance.childNodes.length > 0 &&
			instance.lastChild.tagName !== 'P'
		) {

			instance.appendChild(EditorParagraph.getEmpty());

		}
	};

	// Ensure HTML consistency
	static _nodeText(instanceId, node) {

		const parentElement = node.parentElement;

		if(parentElement === null) {
			return false;
		}

		if(
			parentElement.getAttribute('contenteditable') === 'true' &&
			parentElement.tagName !== 'FIGCAPTION'
		) {

			const nodeText = node.cloneNode(true);

			const nodeP = document.createElement('p');
			nodeP.setAttribute('data-location', EditorParagraph.tick());
			nodeP.appendChild(nodeText);

			if(EditorRange.startContainer === node) {
				EditorRange.newStartContainer = nodeText;
			}

			if(EditorRange.endContainer === node) {
				EditorRange.newEndContainer = nodeText;
			}

			node.parentElement.replaceChild(nodeP, node);


		} else {

			switch(parentElement.tagName) {

				case 'FIGCAPTION' :

					// Figcaption
					EditorFigure.updateCaption(parentElement.parentElement);

					break;

				default : // Nothing to do
					return false;

			}

		}

		return true;

	};

	static _nodeElement(instanceId, node) {

		// Parent node expected
		if(node.parentElement === null) {
			return false;
		}

		// Special case for data-widget node
		if(node.hasAttribute('data-widget')) {

			if(node.tagName === 'FIGURE') {
				EditorMutation._figureClean(node);
			}

			return false;

		}

		let nodeCheck = node;

		while(nodeCheck) {

			// Remove the node if it is within a <figcaption>
			if(
				node.tagName !== 'FIGCAPTION' &&
				nodeCheck.tagName === 'FIGCAPTION'
			) {
				return EditorNode.removeRecursive(node);
			}

			// Ok, this is contenteditable (ie: editor or blockquote)
			if(
				nodeCheck.getAttribute('contenteditable') === 'true' &&
				nodeCheck.tagName !== 'FIGCAPTION'
			) {
				break;
			}

			// Update figcaption if necessary
			if(
				nodeCheck.tagName === 'FIGCAPTION' &&
				nodeCheck.parentElement.tagName === 'FIGURE' &&
				nodeCheck.parentElement.hasAttribute('data-widget')
			) {
				EditorFigure.updateCaption(nodeCheck.parentElement);
			}

			// Do nothing if the node or a parent has data-widget attribute
			if(nodeCheck.hasAttribute('data-widget')) {
				return false;
			}

			nodeCheck = nodeCheck.parentElement;

		}

		return EditorMutation._nodeCleanRecursive(instanceId, node);

	};

	// Clean figure
	static _figureClean(node) {

		// Check for Copy/paste
		// Figures must have a unique ID

		const instance = Editor.getEditorNode(node);

		if(instance.qsa('[id="'+ node.id +'"]').length > 1) {
			node.setAttribute('id', 'editor-figure-'+ EditorFigure.counter++);
		}

	};

	// Clean a node and fix/remove it if not valid
	static _nodeCleanRecursive(instanceId, node) {

		// Handle child nodes
		for(let i = 0; i < node.childNodes.length; i++) {

			const childNode = node.childNodes[i];

			if(childNode.nodeType === Node.ELEMENT_NODE) {

				if(EditorMutation._nodeElement(instanceId, childNode) === 'removed') {
					i--;
				}

			}

		}

		const nodeTag = node.tagName;

		switch(nodeTag) {

			case 'STRONG' :
				return EditorMutation._nodeReplace(node, 'B');

			case 'EN' :
				return EditorMutation._nodeReplace(node, 'I');

			case 'STRIKE' :
				return EditorMutation._nodeReplace(node, 'S');

			// Clean attributes
			case 'B' :
			case 'I' :
			case 'S' :
			case 'U' :
				return EditorMutation._nodeAttributes(node);

			case 'IMG' :
				return EditorNode.remove(node);

			case 'UL' :
			case 'OL' :
			case 'LI' :

				EditorNode.trim(node);
				return EditorMutation._nodeAttributes(node, [], [], true);

			case 'BR' :
				// Convert top <br> to <p>
				if(Editor.isEditorNode(node.parentElement)) {
					return EditorMutation._nodeReplace(node, 'P');
				}
				// Drop <br> that are not in an empty <p>
				else if(
					node.parentElement.tagName !== 'P' ||
					node.parentElement.childNodes.length !== 1
				) {
					//return EditorNode.remove(node);
					// Firefox est débile
					// Il rajoute un <br> en fin de paragraphe quand on tape "espace" alors que les autres navigateurs mettent un &nbsp;
					return EditorMutation._nodeAttributes(node);
				} else {
					return EditorMutation._nodeAttributes(node);
				}

			case 'DIV' :

				// <div> within grids are angels
				if(node.parentElement.parentElement.getAttribute('data-type') === 'grid') {
					return EditorMutation._nodeAttributes(node, ['contenteditable', 'tabindex']);
				}

				// Top <p/div/..> are angels
				if(
					Editor.isEditorNode(node.parentElement) ||
					node.parentNode.getAttribute('contenteditable') === 'true'
				) {
					EditorNode.trim(node);
					return EditorMutation._nodeReplace(node, 'P');
				}
				// Others <p/div/...> are evil
				else {
					return EditorNode.remove(node);
				}

			case 'P' :

				// Top <p/div/..> are angels
				if(
					Editor.isEditorNode(node.parentElement) ||
					node.parentNode.getAttribute('contenteditable') === 'true'
				) {

					EditorNode.trim(node);

					return EditorMutation._nodeAttributes(node, [], ['data-embed', 'data-header', 'data-align']);

				}
				// Others <p/div/...> are evil
				else {
					return EditorNode.remove(node);
				}

			case 'BLOCKQUOTE' :
				// <blockquote> within figures are angels
				if(node.parentElement.parentElement.getAttribute('data-type') === 'quote') {
					return EditorMutation._nodeAttributes(node, ['contenteditable', 'tabindex']);
				}
				// Others are evil
				else {
					return EditorNode.remove(node);
				}

			case 'A' :
				return EditorMutation._nodeAttributes(node, ['href'], [], true);

			// Browsers add span because contenteditable SUCKS
			// Keep these <span> empty (can't remove them because it break Ctrl+Z)
			// A better solution would be to use <span> for formatting (<b>, <i>, <u>, ...)
			case 'SPAN' :
				return EditorMutation._nodeAttributes(node);

			default :
				return EditorNode.remove(node);

		}

		return true;

	};

	// Clean node attributes
	static _nodeAttributes(node, requiredAttributes, authorizedAttributes, requiredChildrenNodes) {

		if(
			typeof requiredChildrenNodes !== 'undefined' && requiredChildrenNodes &&
			node.childNodes.length === 0
		) {
			return EditorNode.remove(node);
		}

		if(typeof requiredAttributes === 'undefined') {
			requiredAttributes = [];
		}

		if(typeof authorizedAttributes === 'undefined') {
			authorizedAttributes = [];
		}

		let requiredFound = 0;

		for(let i = 0; i < node.attributes.length; i++) {

			const attribute = node.attributes[i];

			const index = requiredAttributes.indexOf(attribute.name);
			const indexAuthorized = authorizedAttributes.indexOf(attribute.name);

			if(index > -1) {
				requiredFound++;
			} else if(indexAuthorized === -1) {
				node.removeAttribute(attribute.name);
				i--;
			}

		}

		if(requiredAttributes.length === requiredFound) {
			return 'updated';
		} else {
			return EditorNode.remove(node);
		}

	};

	// Replace a node by another one
	static _nodeReplace(node, tagTo, requiredAttributes) {

		// Check attributes
		if(EditorMutation._nodeAttributes(node, requiredAttributes) === 'removed') {
			return 'removed';
		}

		// Transform tag
		if(node.tagName !== tagTo) {

			const element = document.createElement(tagTo);

			// Copy attributes
			for(let i = 0; i < node.attributes.length; i++) {
				const attribute = node.attributes[i];
				element.setAttribute(attribute.name, attribute.value);
			}

			while(node.childNodes.length > 0) {

				const currentNode = node.childNodes[0];

				element.appendChild(currentNode);

				if(currentNode === EditorRange.startContainer) {
					EditorRange.newStartContainer = currentNode;
				}

				if(currentNode === EditorRange.endContainer) {
					EditorRange.newEndContainer = currentNode;
				}

			}

			const updateStartContainer = (node === EditorRange.startContainer);
			const updateEndContainer = (node === EditorRange.endContainer);

			node.parentElement.replaceChild(element, node);

			if(updateStartContainer) {
				EditorRange.newStartContainer = element;
			}

			if(updateEndContainer) {
				EditorRange.newEndContainer = element;
			}

			node = element;

		}

		// <p> element can't be empty
		if(node.tagName === 'P') {

			EditorParagraph.repair(node);

		}
		return 'updated';

	};

};

// Helper function to manipulate nodes
class EditorNode {

	// Get nextSibling or previousSibling and ignores empty text nodes
	static siblingNoWhitespaces(node, sibling) {

		let nodeSibling = node[sibling];

		for(; ; ) {

			if(nodeSibling === null) {
				return null;
			}

			// Ignore empty nodes
			if(
				nodeSibling.nodeType !== Node.TEXT_NODE ||
				nodeSibling.nodeValue.match(/^[\n\r\t]*$/) === null
			) {
				return nodeSibling;
			}

			nodeSibling = nodeSibling[sibling];

		}

	};

	// Trim whitespaces (\r, \t, \n) in a node
	static trim(node) {

		let childNode;

		function trimIt(node) {

			if(
				node.nodeType === Node.TEXT_NODE &&
				node.nodeValue.match(/^[\n\r\t]*$/)
			) {
				node.parentElement.removeChild(node);
				return true;
			} else {
				return false;
			}

		};

		// Left trim
		while(node.childNodes.length > 0) {

			childNode = node.childNodes[0];

			if(trimIt(childNode) === false) {
				break;
			}

		}

		// Right trim
		while(node.childNodes.length > 0) {

			childNode = node.childNodes[node.childNodes.length - 1];

			if(trimIt(childNode) === false) {
				break;
			}

		}

	};

	// Remove a node from the dom and attach its children to its parent node
	// Requires a EditorRange.saveState() call before
	static remove(node) {

		if(EditorRange.isStateSaved() === false) {
			throw 'Saved state required...';
		}

		const parent = node.parentElement;

		let firstChildHit = false;

		while(node.childNodes.length > 0) {

			if(firstChildHit === false) {

				if(node === EditorRange.startContainer) {
					EditorRange.newStartContainer = node.firstChild;
				}

				if(node === EditorRange.endContainer) {
					EditorRange.newEndContainer = node.firstChild;
				}

				firstChildHit = true;

			}

			if(Editor.isEditorNode(parent)) {

				const childContainer = document.createElement('p');
				childContainer.setAttribute('data-location', EditorParagraph.tick());
				childContainer.appendChild(node.firstChild);

				parent.insertBefore(childContainer, node);

			} else {
				parent.insertBefore(node.firstChild, node);
			}


		}

		parent.removeChild(node);

		return 'removed';

	};

	// Recursively remove a node from the dom and attach its children to its parent node
	static removeRecursive(node) {

		if(EditorRange.isStateSaved() === false) {
			throw 'Saved state required...';
		}

		// Handle child nodes
		for(let i = 0; i < node.childNodes.length; i++) {

			const childNode = node.childNodes[i];

			if(childNode.nodeType === Node.ELEMENT_NODE) {

				EditorNode.removeRecursive(childNode);
				i--;

			}

		}

		EditorNode.remove(node);

	};

};