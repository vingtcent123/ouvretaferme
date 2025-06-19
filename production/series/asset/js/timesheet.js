document.delegateEventListener('input', '.timesheet-time-field input[name="timeAddHour"], .timesheet-time-field input[name="timeAddMinute"]', function(e) {
	Timesheet.updateInputTime(e.target.firstParent('.timesheet-time-field'));
});

document.delegateEventListener('focusin', '.timesheet-time-field input[name="timeAddHour"], .timesheet-time-field input[name="timeAddMinute"]', function(e) {
	Timesheet.updateFocusInTime(e.target);
});

document.delegateEventListener('focusout', '.timesheet-time-field input[name="timeAddHour"], .timesheet-time-field input[name="timeAddMinute"]', function(e) {
	Timesheet.updateFocusOutTime(e.target);
});

class Timesheet {

	static updateSign(target) {

		const current = target.innerHTML;
		target.innerHTML = target.getAttribute('title');
		target.setAttribute('title', current);

		target.dataset.sign = (target.dataset.sign === '+') ? '-' : '+';

		this.updateRealTime(target.firstParent('.timesheet-time-field'));

	}

	static updateInputTime(wrapper) {

		const hour = wrapper.qs('input[name="timeAddHour"]');
		const minute = wrapper.qs('input[name="timeAddMinute"]');

		if(
			hour.matches(':focus') &&
			hour.value != ''
		) {

			if(minute.value === '') {
				minute.value = '00';
			}

		}

		if(
			minute.matches(':focus') &&
			minute.value != ''
		) {

			if(hour.value === '') {
				hour.value = '0';
			}

		}

		this.updateRealTime(wrapper);

	}

	static updateFocusInTime(target) {

		target.setAttribute('placeholder', target.value);
		target.value = '';

	}

	static updateFocusOutTime(target) {

		if(target.value === '') {
			target.value = target.getAttribute('placeholder');
		}

		this.format(target);

		target.setAttribute('placeholder', '--');

		this.updateRealTime(target.firstParent('.timesheet-time-field'));

	}

	static updateRealTime(wrapper) {

		const time = wrapper.qs('input[name="timeAdd"]');
		const sign = (wrapper.qs('.timesheet-time-field-sign').dataset.sign === '+') ? 1 : -1;

		const hour = wrapper.qs('input[name="timeAddHour"]');

		const minute = wrapper.qs('input[name="timeAddMinute"]');

		const hourValue = parseInt(hour.value);
		const minuteValue = parseInt(minute.value);

		if(isNaN(hourValue) === false && isNaN(minuteValue) === false) {
			time.value = sign * (hourValue + minuteValue / 60);
		} else {
			time.value = '';
		}

	}

	static previousDate(target) {
		this.changeDate(target, -1);
	}

	static nextDate(target) {
		this.changeDate(target, 1);
	}

	static changeDate(target, value) {

		const field = target.firstParent('.timesheet-update-item-date-field').qs('input');

		const newDate = new Date(field.value);
		newDate.setDate(newDate.getDate() + value);

		field.value = newDate.toISOString().split('T')[0];


	}

	static updateShortcutSign(target) {
		target.innerHTML = (target.innerHTML === '+') ? '-' : '+';
	}

	static changeShortcut(nodeShortcut) {

		const sign = nodeShortcut.firstParent('.timesheet-update-item-shortcuts').qs('.timesheet-update-item-sign').innerHTML === '+' ? 1 : -1;

		const increment = parseFloat(nodeShortcut.dataset.value);
		const incrementHour = Math.floor(increment);
		const incrementMinute = Math.round((increment - incrementHour) * 60);

		const wrapper = nodeShortcut.firstParent('form').qs('.timesheet-time-field');

		const hour = wrapper.qs('input[name="timeAddHour"]');
		const minute = wrapper.qs('input[name="timeAddMinute"]');

		let hourValue = parseInt(hour.value);
		let minuteValue = parseInt(minute.value);

		if(isNaN(hourValue)) {
			hourValue = 0;
		}

		if(isNaN(minuteValue)) {
			minuteValue = 0;
		}

		minuteValue += incrementMinute * sign;

		if(minuteValue >= 60) {
			minuteValue -= 60;
			hourValue++;
		} else if(minuteValue < 0) {
			minuteValue += 60;
			hourValue--;
		}

		hourValue += incrementHour * sign;

		if(hourValue >= 0) {
			hour.value = hourValue;
			minute.value = minuteValue;
		} else {
			hour.value = '';
			minute.value = '';
		}

		this.format(hour);
		this.format(minute);

		this.updateInputTime(wrapper);

	}

	static format(target) {

		if(target.value.startsWith('-') || isNaN(parseInt(target.value))) {
			target.value = '';
		}

		if(target.value.length >= 2) {
			target.value = target.value.slice(0, 2);
		}

		if(target.matches('[name="timeAddMinute"]') &&target.value.length === 1) {
			target.value = '0' + target.value;
		}

	}

}