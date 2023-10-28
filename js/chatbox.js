document.addEventListener('DOMContentLoaded', function () {
    jQuery(document).ready(function ($) {
        $('.reason-select').select2();
    });

    const chatbox = document.querySelector('.chatbox');
    const messageBox = chatbox.querySelector('.chatbox-messages');
    const sendButton = chatbox.querySelector('#send-button');
    let userInput = chatbox.querySelector('#user-input');
    let reasonSelectEle = chatbox.querySelector('.reason-select');
    const textInputEle = document.querySelector('.chatbox-input .normal_text_input');

    let answers = {};

    // Function to add a user message to the chat
    function addUserMessage(message, elements = []) {
        const userMessage = document.createElement('div');
        userMessage.classList.add('message', 'sent');
        messageBox.appendChild(userMessage);

        const messageContent = document.createElement('div');
        messageContent.classList.add('message-content');
        messageContent.textContent = message;
        userMessage.appendChild(messageContent);

        elements.forEach(element => {
            messageContent.appendChild(element);
        });

        scrollToBottom();
    }

    // Function to add a bot message with typing animation
    function addBotMessage(message, elements = []) {
        const botMessage = document.createElement('div');
        botMessage.classList.add('message', 'received');
        messageBox.appendChild(botMessage);

        const messageContent = document.createElement('div');
        messageContent.classList.add('message-content');
        messageContent.textContent = message;
        botMessage.appendChild(messageContent);

        elements.forEach(element => {
            messageContent.appendChild(element);
        });

        scrollToBottom();
    }

    // Function to add a calendar input and submit button
    function calendarInput() {
        const calendarInput = document.createElement('input');
        calendarInput.type = 'date';
        calendarInput.id = 'calendar-input';
        calendarInput.classList.add('calendar-input');

        return calendarInput;
    }

    // Promisify the click event
    function clickEventPromise(element) {
        return new Promise((resolve) => {
            element.addEventListener('click', resolve);
        });
    }

    // Ask a question and wait for an answer
    async function getAnswer(errMessage, userInputEle, validationFunc) {
        userInput.innerHTML = '';
        if (userInputEle) {
            userInput.append(userInputEle);
        } else {
            userInput.append(textInputEle);
        }

        while (true) {
            await clickEventPromise(sendButton);
            if (userInputEle.tagName.toLowerCase() === 'select' && userInputEle.multiple){
                let answer = getSelectValues(userInputEle);
                if (answer.length!=0) {
                    return answer;
                } else {
                    addBotMessage(errMessage);
                }
            }else{
                let answer = userInput.firstChild.value;
                if (validationFunc(answer)) {
                    return answer;
                } else {
                    if(errMessage!=''){addBotMessage(errMessage);}
                    else{return answer}
                }
            }
            
        }
    }

    function populateSymptomSelect(callback) {
        jQuery(document).ready(function ($) {
            $.ajax({
                type: 'POST',
                url: myAjax.ajaxurl,
                data: {
                    action: 'fetch_specific_data',
                    disease: answers['reason']
                },
                success: function (data) {
                    var ajaxResponseData = JSON.parse(data); // Store the response in the global variable
                    console.log('fetched post data');
                    console.log(ajaxResponseData);
                    callback(ajaxResponseData);
                }
            });
        });
    }

    function getSelectValues(select) {
        var result = [];
        var options = select && select.options;
        var opt;
      
        for (var i=0, iLen=options.length; i<iLen; i++) {
          opt = options[i];
      
          if (opt.selected) {
            result.push(opt.value || opt.text);
          }
        }
        return result;
      }
      
      function yesNoSelectEle(){
        const yesNoSelect = document.createElement('select');
        yesNoSelect.classList.add('yes-no-select');

        const yesOption = document.createElement('option');
        yesOption.value = 'Yes';
        yesOption.text = 'Yes';

        const noOption = document.createElement('option');
        noOption.value = 'No';
        noOption.text = 'No';

        yesNoSelect.appendChild(yesOption);
        yesNoSelect.appendChild(noOption);
        return yesNoSelect;
      }

    // Main chatbot logic
    (async function () {
        // 1st question
        addBotMessage('Welcome to Parkki Terveyspalvelut chat service!Please select a reason for you visit from the menu below');
        const reason = await getAnswer('Please select your reason.', reasonSelectEle, answer => answer !== '');
        addUserMessage(`Your selected reason is ${reason}`);
        answers['reason'] = reason;

        // 2nd question

        addBotMessage(`A few more questions regarding your chosen symptom "${reason}" before you meet the doc! When did your symptoms start?`);
        const symptomStartingDate = await getAnswer(`Please select starting date`, calendarInput(), answer => answer !== '');
        addUserMessage(`Your symptoms starting date ${symptomStartingDate}`)
        answers['symptomStartingDate'] = symptomStartingDate;

        // Process the start date here

        // 3rd question
        
        // addBotMessage('What is the third question?');
        // const thirdAnswer = await getAnswer('');

        populateSymptomSelect(async function (ajaxResponseData) {
            // Create a select element using pure JavaScript
            var symptomSelect = document.createElement('select');
            symptomSelect.classList.add('symptom-select');
            symptomSelect.multiple = true;
        
            // Get the extracted symptoms from the response
            var extractedSymptoms = ajaxResponseData[0].symptoms.split('|');
        
        
            // Loop through the symptoms and create and append options
            extractedSymptoms.forEach(function (symptom) {
                if (symptom.trim() !== '') {
                    var option = document.createElement('option');
                    option.value = symptom;
                    option.text = symptom;
                    symptomSelect.appendChild(option);
                }
            });
        
            // Initialize Select2 for the select element with a placeholder
            jQuery(document).ready(function ($) {
                $(symptomSelect).select2({
                    placeholder: 'Select symptoms' // Set the placeholder text
                });
            });
        
            // Now you can use symptomSelect and ajaxResponseData outside of the AJAX function
            console.log(ajaxResponseData);
            console.log(symptomSelect);
        
            // Use it in getAnswer or any other part of your code
            addBotMessage("Please select from the menu below all the symptoms you might have");
            const symptoms = await getAnswer('Please select your symptoms', symptomSelect, answer => answer !== '');
            answers['symptoms'] = symptoms;
            addUserMessage(`your symptoms are ${symptoms}`)


            //4th question
            addBotMessage('Did you have any other symptoms not listed above?');
            const hasAdditionalSymptoms = await getAnswer('Please select "Yes" or "No"', yesNoSelectEle(), answer => answer === 'Yes' || answer === 'No');
            if (hasAdditionalSymptoms === 'Yes') {
                // Handle additional symptoms logic if "Yes" is selected
                addBotMessage("Please specify what other symptom(s) you have below");
                const additionalSymptoms = await getAnswer('', textInputEle, answer => answer);
                if(additionalSymptoms!=''){addUserMessage(additionalSymptoms);}
                answers['additionalSymptoms'] = additionalSymptoms;
            } else {
                answers['additionalSymptoms'] = 'no';

            }

            addBotMessage("Thank you for answering the questionnaire. I will now direct you to the queue to await a doctor. Average wait time is 1h 1min 1sec. Please do not close the chat. The cotor will be with you shortly")
            
            //cleaning of the user input
            userInput.innerHTML = '';
            userInput.append(textInputEle);


            jQuery(document).ready(function ($) {
                $.ajax({
                    type: 'POST',
                    url: myAjax.ajaxurl,
                    data: {
                        action: 'insert_enquiry_post_type',
                        reason: answers['reason'],
                        symptomStartingDate: answers['symptomStartingDate'],
                        symptoms: answers['symptoms'],
                        additionalSymptoms: answers['additionalSymptoms'],
                    },
                    success: function (data) {
                        // Handle the response from the server if needed
                        // console.log(data);
                    }
                });
            });

            location.reload(true);
            location.reload(true);
            


        });
        

        
        

        

        

        // Process the third answer here

        // Continue with more questions if needed
    })();

    // console.log(answers);

    // Function to scroll to the bottom of the chatbox
    function scrollToBottom() {
        messageBox.scrollTop = messageBox.scrollHeight;
    }
});
